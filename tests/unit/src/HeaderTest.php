<?php

namespace AvalancheDevelopment\SwaggerHeaderMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
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

    public function testCheckIncomingContentReturnsTrueIfEmptyBody()
    {
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->expects($this->once())
            ->method('getSize')
            ->willReturn(null);

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStream);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedCheckIncomingContent = $reflectedHeader->getMethod('checkIncomingContent');
        $reflectedCheckIncomingContent->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'extractContentHeader'
            ])
            ->getMock();
        $header->expects($this->never())
            ->method('extractContentHeader');

        $result = $reflectedCheckIncomingContent->invokeArgs($header, [
            $mockRequest,
            [],
        ]);

        $this->assertTrue($result);
    }

    public function testCheckIncomingContentReturnsTrueOnMatch()
    {
        $contentHeader = [
            'application/json',
        ];
        $consumeTypes = [
            'application/vnd.github+json',
            'application/json',
        ];

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStream);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedCheckIncomingContent = $reflectedHeader->getMethod('checkIncomingContent');
        $reflectedCheckIncomingContent->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'extractContentHeader'
            ])
            ->getMock();
        $header->expects($this->once())
            ->method('extractContentHeader')
            ->willReturn($contentHeader);

        $result = $reflectedCheckIncomingContent->invokeArgs($header, [
            $mockRequest,
            $consumeTypes,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckIncomingContentReturnsFalseIfNotPassed()
    {
        $contentHeader = [];
        $consumeTypes = [
            'application/vnd.github+json',
            'application/json',
        ];

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStream);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedCheckIncomingContent = $reflectedHeader->getMethod('checkIncomingContent');
        $reflectedCheckIncomingContent->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'extractContentHeader'
            ])
            ->getMock();
        $header->expects($this->once())
            ->method('extractContentHeader')
            ->willReturn($contentHeader);

        $result = $reflectedCheckIncomingContent->invokeArgs($header, [
            $mockRequest,
            $consumeTypes,
        ]);

        $this->assertFalse($result);
    }

    public function testCheckIncomingContentReturnsFalseIfNoMatch()
    {
        $contentHeader = [
            'text/plain',
        ];
        $consumeTypes = [
            'application/vnd.github+json',
            'application/json',
        ];

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStream);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedCheckIncomingContent = $reflectedHeader->getMethod('checkIncomingContent');
        $reflectedCheckIncomingContent->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'extractContentHeader'
            ])
            ->getMock();
        $header->expects($this->once())
            ->method('extractContentHeader')
            ->willReturn($contentHeader);

        $result = $reflectedCheckIncomingContent->invokeArgs($header, [
            $mockRequest,
            $consumeTypes,
        ]);

        $this->assertFalse($result);
    }

    public function testExtractContentHeaderHandlesMultipleTypes()
    {
        $contentTypes = 'application/vnd.github+json, application/json';
        $contentTypeCount = 2;

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getHeader')
            ->with('content')
            ->willReturn($contentTypes);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedExtractContentHeader = $reflectedHeader->getMethod('extractContentHeader');
        $reflectedExtractContentHeader->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $reflectedExtractContentHeader->invokeArgs($header, [
            $mockRequest,
        ]);

        $this->assertCount($contentTypeCount, $result);
    }

    public function testExtractContentHeaderParsesOutOptions()
    {
        $contentType = 'text/plain; charset=utf8';
        $extractedContentHeader = [
            'text/plain',
        ];

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getHeader')
            ->with('content')
            ->willReturn($contentType);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedExtractContentHeader = $reflectedHeader->getMethod('extractContentHeader');
        $reflectedExtractContentHeader->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $reflectedExtractContentHeader->invokeArgs($header, [
            $mockRequest,
        ]);

        $this->assertEquals($extractedContentHeader, $result);
    }

    public function testExtractContentHeaderLowersCasing()
    {
        $contentType = 'Application/Json';
        $casedContentHeader = [
            'application/json',
        ];

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getHeader')
            ->with('content')
            ->willReturn($contentType);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedExtractContentHeader = $reflectedHeader->getMethod('extractContentHeader');
        $reflectedExtractContentHeader->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $reflectedExtractContentHeader->invokeArgs($header, [
            $mockRequest,
        ]);

        $this->assertEquals($casedContentHeader, $result);
    }

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
