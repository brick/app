<?php

declare(strict_types=1);

namespace Brick\App\Route;

use Brick\App\Route;
use Brick\App\RouteMatch;
use Brick\Http\Request;

/**
 * An annotation-based route implementation.
 */
class AnnotationRoute implements Route
{
    /**
     * A list of routes. Each route is an array containing:
     *
     *   0: pathRegexp
     *   1: httpMethods
     *   2: priority
     *   3: className
     *   4: methodName
     *   5: classParameterNames
     *   6: methodParameterNames
     *
     * @var array
     */
    private array $routes;

    /**
     * AnnotationRoute constructor.
     *
     * @param array $routes An array of routes built by AnnotationRouteCompiler::compile().
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * {@inheritdoc}
     */
    public function match(Request $request) : ?RouteMatch
    {
        $path = $request->getPath();
        $httpMethod = $request->getMethod();

        $matchingRoutes = [];

        foreach ($this->routes as $route) {
            $pathRegexp = $route[0];

            if (preg_match($pathRegexp, $path, $matches) === 1) {
                $httpMethods = $route[1];

                if ($httpMethods && ! in_array($httpMethod, $httpMethods, true)) {
                    continue;
                }

                $route[] = $matches;
                $matchingRoutes[] = $route;
            }
        }

        $route = $this->getHighestPriorityRoute($matchingRoutes);

        if ($route === null) {
            return null;
        }

        [, , , $className, $methodName, $classParameterNames, $methodParameterNames, $matches] = $route;

        $classParameters = [];
        $methodParameters = [];

        $index = 1;

        foreach ($classParameterNames as $name) {
            $classParameters[$name] = $matches[$index++];
        }

        foreach ($methodParameterNames as $name) {
            $methodParameters[$name] = $matches[$index++];
        }

        return RouteMatch::forMethod($className, $methodName, $classParameters, $methodParameters);
    }

    private function getHighestPriorityRoute(array $routes): ?array
    {
        $highestPriority = null;
        $highestPriorityRoute = null;

        foreach ($routes as $route) {
            $priority = $route[2];

            if ($highestPriority === null || $priority > $highestPriority) {
                $highestPriority = $priority;
                $highestPriorityRoute = $route;
            }
        }

        return $highestPriorityRoute;
    }
}
