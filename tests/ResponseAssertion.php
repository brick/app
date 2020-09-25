<?php

namespace Brick\App\Tests;

use Brick\Http\Response;
use PHPUnit\Framework\TestCase;

class ResponseAssertion
{
    private TestCase $testCase;

    private Response $response;

    public function __construct(TestCase $testCase, Response $response)
    {
        $this->testCase = $testCase;
        $this->response = $response;
    }

    public function hasStatusCode(int $statusCode) : ResponseAssertion
    {
        $this->testCase->assertSame($statusCode, $this->response->getStatusCode());

        return $this;
    }

    public function hasBody(string $body) : ResponseAssertion
    {
        $this->testCase->assertSame($body, (string) $this->response->getBody());

        return $this;
    }
}
