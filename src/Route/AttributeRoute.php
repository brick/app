<?php

declare(strict_types=1);

namespace Brick\App\Route;

use Brick\App\Route;
use Brick\App\RouteMatch;
use Brick\Http\Request;

/**
 * An attribute-based route implementation.
 */
class AttributeRoute implements Route
{
    /**
     * A map of regexp to [className, methodName, classParameterNames, methodParameterNames].
     */
    private array $routes;

    /**
     * AttributeRoute constructor.
     *
     * @param array $routes An array of routes built by AttributeRouteCompiler::compile().
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function match(Request $request) : RouteMatch|null
    {
        $path = $request->getPath();
        $httpMethod = $request->getMethod();

        foreach ($this->routes as $values) {
            [$regexp] = $values;

            if (preg_match($regexp, $path, $matches) === 1) {
                [, $httpMethods] = $values;

                if ($httpMethods && ! in_array($httpMethod, $httpMethods, true)) {
                    continue;
                }

                [, , $className, $methodName, $classParameterNames, $methodParameterNames] = $values;

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
        }

        return null;
    }
}
