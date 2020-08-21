<?php

namespace functional;

use Stack;
use Stack\CallableHttpKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RunTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function runShouldRenderPage()
    {
        $this->expectOutputString('Hello World!');

        $app = new CallableHttpKernel(function ($request) {
            return new Response('Hello World!');
        });

        Stack\run($app);
    }
}
