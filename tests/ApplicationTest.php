<?php

namespace Brick\App\Tests;

use PHPUnit\Framework\TestCase;

use Brick\App\Application;
use Brick\App\Route;
use Brick\App\RouteMatch;
use Brick\Http\MessageBodyString;
use Brick\Http\Request;
use Brick\Http\Response;

class ApplicationTest extends TestCase
{
    private function assertResponse(Response $response) : ResponseAssertion
    {
        return new ResponseAssertion($this, $response);
    }

    private function assertStatusCode(int $statusCode, Response $response) : void
    {
        $this->assertSame($statusCode, $response->getStatusCode());
    }

    public function testNoRouteReturns404() : void
    {
        $application = Application::create();
        $response = $application->handle(new Request());
        $this->assertStatusCode(404, $response);
    }

    public function testRouting() : void
    {
        $application = Application::create();
        $application->addRoute(new HelloRoute());

        $request = new Request();
        $this->assertResponse($application->handle($request))
            ->hasStatusCode(404);

        $request = $request->withPath('/a');
        $this->assertResponse($application->handle($request))
            ->hasStatusCode(200)
            ->hasBody('Hello');

        $request = $request->withPath('/b');
        $this->assertResponse($application->handle($request))
            ->hasStatusCode(200)
            ->hasBody('World');

        $request = $request->withPath('/c');
        $this->assertResponse($application->handle($request))
            ->hasStatusCode(404);
    }
}

class HelloRoute implements Route
{
    public function match(Request $request) : RouteMatch|null
    {
        if ($request->getPath() === '/a') {
            return RouteMatch::forFunction(function() {
                return (new Response())->withBody(new MessageBodyString('Hello'));
            });
        }

        if ($request->getPath() === '/b') {
            return RouteMatch::forFunction(function() {
                return (new Response())->withBody(new MessageBodyString('World'));
            });
        }

        return null;
    }
}
