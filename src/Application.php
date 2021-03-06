<?php

declare(strict_types=1);

namespace Brick\App;

use Brick\App\Event\ControllerInvocatedEvent;
use Brick\App\Event\ControllerReadyEvent;
use Brick\App\Event\UncaughtExceptionEvent;
use Brick\App\Event\HttpExceptionEvent;
use Brick\App\Event\IncomingRequestEvent;
use Brick\App\Event\NonResponseResultEvent;
use Brick\App\Event\ResponseReceivedEvent;
use Brick\App\Event\RouteMatchedEvent;
use Brick\Event\EventDispatcher;
use Brick\DI\Injector;
use Brick\DI\InjectionPolicy;
use Brick\DI\ValueResolver;
use Brick\DI\Container;
use Brick\DI\ValueResolver\DefaultValueResolver;
use Brick\Http\Exception\HttpException;
use Brick\Http\Exception\HttpInternalServerErrorException;
use Brick\Http\Exception\HttpNotFoundException;
use Brick\Http\Request;
use Brick\Http\RequestHandler;
use Brick\Http\Response;
use ReflectionMethod;
use ReflectionFunction;
use Throwable;
use UnexpectedValueException;

/**
 * The web application kernel.
 */
class Application implements RequestHandler
{
    private Injector $injector;

    private ControllerValueResolver $valueResolver;

    private EventDispatcher $eventDispatcher;

    /**
     * @var Route[]
     */
    private array $routes = [];

    /**
     * Class constructor.
     */
    private function __construct(ValueResolver $resolver, InjectionPolicy $policy)
    {
        $this->valueResolver   = new ControllerValueResolver($resolver);
        $this->injector        = new Injector($this->valueResolver, $policy);
        $this->eventDispatcher = new EventDispatcher();
    }

    /**
     * Creates an application.
     *
     * If a dependency injection container is provided, it is used to automatically inject dependencies in controllers.
     *
     * @param Container|null $container
     *
     * @return Application
     */
    public static function create(Container|null $container = null) : Application
    {
        if ($container !== null) {
            $valueResolver   = $container->getValueResolver();
            $injectionPolicy = $container->getInjectionPolicy();
        } else {
            $valueResolver   = new DefaultValueResolver();
            $injectionPolicy = new InjectionPolicy\NullPolicy();
        }

        return new Application($valueResolver, $injectionPolicy);
    }

    public function addRoute(Route $route) : Application
    {
        $this->routes[] = $route;

        return $this;
    }

    /**
     * @param Plugin $plugin The plugin to add.
     *
     * @return Application This instance, for chaining.
     */
    public function addPlugin(Plugin $plugin) : Application
    {
        $plugin->register($this->eventDispatcher);

        return $this;
    }

    /**
     * Runs the application.
     */
    public function run() : void
    {
        $request = Request::getCurrent();
        $response = $this->handle($request);
        $response->send();
    }

    /**
     * Handles the Request and returns a Response.
     */
    public function handle(Request $request) : Response
    {
        try {
            return $this->handleRequest($request);
        } catch (HttpException $e) {
            return $this->handleHttpException($e, $request);
        } catch (Throwable $e) {
            return $this->handleUncaughtException($e, $request);
        }
    }

    public function handleWithoutCatchingExceptions(Request $request): Response
    {
        return $this->handleRequest($request);
    }

    /**
     * Converts an HttpException to a Response.
     */
    private function handleHttpException(HttpException $exception, Request $request) : Response
    {
        $response = (new Response())
            ->withStatusCode($exception->getStatusCode())
            ->withHeaders($exception->getHeaders());

        $event = new HttpExceptionEvent($exception, $request, $response);
        $this->eventDispatcher->dispatch(HttpExceptionEvent::class, $event);

        return $event->getResponse();
    }

    /**
     * Converts an uncaught exception to a Response.
     */
    private function handleUncaughtException(Throwable $exception, Request $request) : Response
    {
        $event = new UncaughtExceptionEvent($exception, $request);
        $this->eventDispatcher->dispatch(UncaughtExceptionEvent::class, $event);

        $httpException = $event->getHttpException();

        if ($httpException === null) {
            $httpException = new HttpInternalServerErrorException('Uncaught exception', $exception);
        }

        return $this->handleHttpException($httpException, $request);
    }

    /**
     * @param Request $request The request to handle.
     *
     * @return Response The generated response.
     *
     * @throws HttpException If a route throws such an exception, or no route matches the request.
     * @throws UnexpectedValueException           If a route or controller returned an invalid value.
     */
    private function handleRequest(Request $request) : Response
    {
        $event = new IncomingRequestEvent($request);
        $this->eventDispatcher->dispatch(IncomingRequestEvent::class, $event);

        $match = $this->route($request);

        $event = new RouteMatchedEvent($request, $match);
        $this->eventDispatcher->dispatch(RouteMatchedEvent::class, $event);

        $controllerReflection = $match->getControllerReflection();
        $instance = null;

        $this->valueResolver->setRequest($request);

        if ($controllerReflection instanceof ReflectionMethod) {
            $className = $controllerReflection->getDeclaringClass()->getName();
            $instance = $this->injector->instantiate($className, $match->getClassParameters());
            $callable = $controllerReflection->getClosure($instance);
        } elseif ($controllerReflection instanceof ReflectionFunction) {
            $callable = $controllerReflection->getClosure();
        } else {
            throw new UnexpectedValueException('Unknown controller reflection type.');
        }

        $event = new ControllerReadyEvent($request, $match, $instance);
        $event->addParameters($match->getFunctionParameters());

        $this->eventDispatcher->dispatch(ControllerReadyEvent::class, $event);

        $response = $event->getResponse();

        if ($response === null) {
            try {
                $result = $this->injector->invoke($callable, $event->getParameters());

                if ($result instanceof Response) {
                    $response = $result;
                } else {
                    $event = new NonResponseResultEvent($request, $match, $instance, $result);
                    $this->eventDispatcher->dispatch(NonResponseResultEvent::class, $event);

                    $response = $event->getResponse();

                    if ($response === null) {
                        throw $this->invalidControllerReturnValue($result);
                    }
                }
            } finally {
                $event = new ControllerInvocatedEvent($request, $match, $instance);
                $this->eventDispatcher->dispatch(ControllerInvocatedEvent::class, $event);
            }
        }

        $event = new ResponseReceivedEvent($request, $response, $match, $instance);
        $this->eventDispatcher->dispatch(ResponseReceivedEvent::class, $event);

        return $event->getResponse();
    }

    /**
     * Routes the given Request.
     *
     * @param Request $request The request.
     *
     * @return RouteMatch The route match.
     *
     * @throws HttpNotFoundException If no route matches the request.
     */
    private function route(Request $request) : RouteMatch
    {
        foreach ($this->routes as $route) {
            try {
                $match = $route->match($request);
            }
            catch (RoutingException $e) {
                throw new HttpNotFoundException($e->getMessage(), $e);
            }

            if ($match !== null) {
                return $match;
            }
        }

        throw new HttpNotFoundException('No route matches the request.');
    }

    /**
     * @param mixed $value The controller return value.
     *
     * @return UnexpectedValueException
     */
    private function invalidControllerReturnValue(mixed $value) : \UnexpectedValueException
    {
        $message  = 'Got a non-Response return value of type %s from controller, ';
        $message .= 'and no registered plugin could create a Response out of the result.';

        return new \UnexpectedValueException(sprintf($message, get_debug_type($value)));
    }
}
