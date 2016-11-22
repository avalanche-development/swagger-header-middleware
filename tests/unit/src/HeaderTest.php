<?php

namespace AvalancheDevelopment\SwaggerHeaderMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;

class HeaderTest extends PHPUnit_Framework_TestCase
{

    public function testImplementsLoggerAwareInterface()
    {
        $header = new Header;

        $this->assertInstanceOf(LoggerAwareInterface::class, $header);
    }

    public function testConstructSetsNullLogger()
    {
        $logger = new NullLogger;

        $header = new Header;

        $this->assertAttributeEquals($logger, 'logger', $header);
    }
}
