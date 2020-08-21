<?php

namespace Noxlogic\RateLimitBundle\Tests\Annotation;

use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Noxlogic\RateLimitBundle\Events\RateLimitEvents;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RateLimitEventsTest extends TestCase
{
    public function testConstants()
    {
        $this->assertEquals('ratelimit.generate.key', RateLimitEvents::GENERATE_KEY);
    }
}
