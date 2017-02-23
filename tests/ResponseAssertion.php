<?php

namespace Brick\App\Tests;

use Brick\Http\Response;

use PHPUnit\Framework\TestCase;

class ResponseAssertion
{
    /**
     * @var TestCase
     */
    private $testCase;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param TestCase $testCase
     * @param Response $response
     */
    public function __construct(TestCase $testCase, Response $response)
    {
        $this->testCase = $testCase;
        $this->response = $response;
    }

    /**
     * @param int $statusCode
     *
     * @return ResponseAssertion
     */
    public function hasStatusCode($statusCode)
    {
        $this->testCase->assertSame($statusCode, $this->response->getStatusCode());

        return $this;
    }

    /**
     * @param string $body
     *
     * @return ResponseAssertion
     */
    public function hasBody($body)
    {
        $this->testCase->assertSame($body, (string) $this->response->getBody());

        return $this;
    }
}
