<?php

namespace AvalancheDevelopment\SwaggerHeaderMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

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

    public function testInvokeBailsIfNoSwaggerFound() {}

    public function testInvokePullsConsumeTypesFromSwagger() {}

    public function testInvokeAllowsRequestIfAcceptableHeader() {}

    public function testInvokeBailsIfUnacceptableHeader() {}

    public function testCheckIncomingHeaderReturnsTrueOnMatch() {}

    public function testCheckIncomingHeaderReturnsFalseIfEmpty() {}

    public function testCheckIncomingHeaderReturnsFalseIfNoMatch() {}

    public function testLog()
    {
        $message = 'test debug message';

        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with("swagger-header-middleware: {$message}");

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedLog = $reflectedHeader->getMethod('log');
        $reflectedLog->setAccessible(true);
        $reflectedLogger = $reflectedHeader->getProperty('logger');
        $reflectedLogger->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedLogger->setValue($header, $mockLogger);
        $reflectedLog->invokeArgs($header, [
            $message,
        ]);       
    }
}
