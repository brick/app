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
     * A map of regexp to [className, methodName, classParameterNames, methodParameterNames].
     *
     * @var array
     */
    private $routes;

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
