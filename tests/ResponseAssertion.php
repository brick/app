<?php

namespace Brick\App\Tests;

use Brick\Http\Response;

class ResponseAssertion
{
    /**
     * @var \PHPUnit_Framework_TestCase
     */
    private $testCase;

    /**
     * @var \Brick\Http\Response
     */
    private $response;

    /**
     * @param \PHPUnit_Framework_TestCase $testCase
     * @param \Brick\Http\Response        $response
     */
    public function __construct(\PHPUnit_Framework_TestCase $testCase, Response $response)
    {
        $this->testCase = $testCase;
        $this->response = $response;
    }

    /**
     * @param integer $statusCode
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
