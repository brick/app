<?php

namespace Brick\App\Tests\Route
{
    use PHPUnit\Framework\TestCase;

    use Brick\App\Route\SimpleRoute;
    use Brick\App\RouteMatch;

    use Brick\Http\Request;

    /**
     * Tests for class SimpleRoute.
     */
    class SimpleRouteTest extends TestCase
    {
        /**
         * @dataProvider providerRoute
         *
         * @param string $path   The request path.
         * @param string $class  The expected controller class.
         * @param string $method The expected controller method.
         */
        public function testRoute($path, $class, $method)
        {
            $request = new Request();
            $request = $request->withPath($path);

            $route = new SimpleRoute([
                '/'         => \name\space\IndexController::class,
                '/foo/'     => \name\space\FooController::class,
                '/foo/bar/' => \name\space\Foo\BarController::class,
            ]);

            $match = $route->match($request);
            $this->assertInstanceOf(RouteMatch::class, $match);

            $reflection = $match->getControllerReflection();
            $this->assertInstanceOf(\ReflectionMethod::class, $reflection);

            /** @var \ReflectionMethod $reflection */
            $this->assertSame($class, $reflection->getDeclaringClass()->getName());
            $this->assertSame($method, $reflection->getName());
        }

        /**
         * @return array
         */
        public function providerRoute()
        {
            return [
                ['/',                'name\space\IndexController',   'indexAction'],
                ['/foo',             'name\space\IndexController',   'fooAction'],
                ['/foo/',            'name\space\FooController',     'indexAction'],
                ['/foo/bar',         'name\space\FooController',     'barAction'],
                ['/foo/bar/',        'name\space\Foo\BarController', 'indexAction'],
                ['/foo/bar/foo-bar', 'name\space\Foo\BarController', 'fooBarAction'],
            ];
        }
    }
}

namespace name\space
{
    class IndexController
    {
        public function indexAction() {}
        public function fooAction() {}
    }

    class FooController
    {
        public function indexAction() {}
        public function barAction() {}
    }
}

namespace name\space\Foo
{
    class BarController
    {
        public function indexAction() {}
        public function fooBarAction() {}
    }
}
