<?php

declare(strict_types=1);

namespace Brick\App;

use Brick\App\Event\ControllerInvocatedEvent;
use Brick\App\Event\ControllerReadyEvent;
use Brick\App\Event\ExceptionCaughtEvent;
use Brick\App\Event\IncomingRequestEvent;
use Brick\App\Event\NonResponseResultEvent;
use Brick\App\Event\ResponseReceivedEvent;
use Brick\App\Event\RouteMatchedEvent;
use Brick\Event\EventDispatcher;
use Brick\Di\Injector;
use Brick\Di\InjectionPolicy;
use Brick\Di\ValueResolver;
use Brick\Di\Container;
use Brick\Di\ValueResolver\DefaultValueResolver;
use Brick\Http\Exception\HttpException;
use Brick\Http\Exception\HttpInternalServerErrorException;
use Brick\Http\Exception\HttpNotFoundException;
use Brick\Http\Request;
use Brick\Http\RequestHandler;
use Brick\Http\Response;

/**
 * The web application kernel.
 */
class Application implements RequestHandler
{
    /**
     * @var \Brick\Di\Injector
     */
    private $injector;

    /**
     * @var \Brick\App\ControllerValueResolver
     */
    private $valueResolver;

    /**
     * @var \Brick\Event\EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var \Brick\App\Route[]
     */
    private $routes = [];

    /**
     * Class constructor.
     *
     * @param ValueResolver   $resolver
     * @param InjectionPolicy $policy
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
    public static function create(?Container $container = null) : Application
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

    /**
     * @param Route $route
     *
     * @return Application
     */
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
     *
     * @return void
     */
    public function run() : void
    {
        $request = Request::getCurrent();
        $response = $this->handle($request);
        $response->send();
    }

    /**
     * @param \Brick\Http\Request $request
     *
     * @return \Brick\Http\Response
     */
    public function handle(Request $request) : Response
    {
        try {
            return $this->handleRequest($request);
        } catch (HttpException $e) {
            return $this->handleHttpException($e, $request);
        } catch (\Throwable $e) {
            return $this->handleUncaughtException($e, $request);
        }
    }

    /**
     * Converts an HttpException to a Response.
     *
     * @param \Brick\Http\Exception\HttpException $exception
     * @param \Brick\Http\Request                 $request
     *
     * @return \Brick\Http\Response
     */
    private function handleHttpException(HttpException $exception, Request $request) : Response
    {
        $response = new Response();

        $response->setContent($exception);
        $response->setStatusCode($exception->getStatusCode());
        $response->setHeaders($exception->getHeaders());
        $response->setHeader('Content-Type', 'text/plain');

        $event = new ExceptionCaughtEvent($exception, $request, $response);
        $this->eventDispatcher->dispatch(ExceptionCaughtEvent::class, $event);

        return $response;
    }

    /**
     * Wraps an uncaught exception in an HttpInternalServerErrorException, and converts it to a Response.
     *
     * @param \Throwable          $exception
     * @param \Brick\Http\Request $request
     *
     * @return \Brick\Http\Response
     */
    private function handleUncaughtException(\Throwable $exception, Request $request) : Response
    {
        $httpException = new HttpInternalServerErrorException('Uncaught exception', $exception);

        return $this->handleHttpException($httpException, $request);
    }

    /**
     * @param \Brick\Http\Request $request The request to handle.
     *
     * @return \Brick\Http\Response The generated response.
     *
     * @throws \Brick\Http\Exception\HttpException If a route throws such an exception, or no route matches the request.
     * @throws \UnexpectedValueException           If a route or controller returned an invalid value.
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

        if ($controllerReflection instanceof \ReflectionMethod) {
            $className = $controllerReflection->getDeclaringClass()->getName();
            $instance = $this->injector->instantiate($className, $match->getClassParameters());
            $callable = $controllerReflection->getClosure($instance);
        } elseif ($controllerReflection instanceof \ReflectionFunction) {
            $callable = $controllerReflection->getClosure();
        } else {
            throw new \UnexpectedValueException('Unknown controller reflection type.');
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
                        throw $this->invalidReturnValue('controller', Response::class, $result);
                    }
                }
            } catch (HttpException $e) {
                $response = $this->handleHttpException($e, $request);
            } finally {
                $event = new ControllerInvocatedEvent($request, $match, $instance);
                $this->eventDispatcher->dispatch(ControllerInvocatedEvent::class, $event);
            }
        }

        $event = new ResponseReceivedEvent($request, $response, $match, $instance);
        $this->eventDispatcher->dispatch(ResponseReceivedEvent::class, $event);

        return $response;
    }

    /**
     * Routes the given Request.
     *
     * @param Request $request The request.
     *
     * @return RouteMatch The route match.
     *
     * @throws HttpNotFoundException     If no route matches the request.
     * @throws \UnexpectedValueException If a route returns an invalid value.
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
                if ($match instanceof RouteMatch) {
                    return $match;
                }

                throw $this->invalidReturnValue('route', Route::class . ' or NULL', $match);
            }
        }

        throw new HttpNotFoundException('No route matches the request.');
    }

    /**
     * @param string $what     The name of the expected resource.
     * @param string $expected The expected return value type.
     * @param mixed  $actual   The actual return value.
     *
     * @return \UnexpectedValueException
     */
    private function invalidReturnValue(string $what, string $expected, $actual) : \UnexpectedValueException
    {
        $message = 'Invalid return value from %s: expected %s, got %s.';
        $actual  = is_object($actual) ? get_class($actual) : gettype($actual);

        return new \UnexpectedValueException(sprintf($message, $what, $expected, $actual));
    }
}
