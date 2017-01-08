<?php

namespace AvalancheDevelopment\SwaggerHeaderMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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

    public function testInvokeBailsIfNoSwaggerFound()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getAttribute')
            ->with('swagger')
            ->willReturn(null);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'attachContentHeader',
                'log',
            ])
            ->getMock();
        $header->expects($this->never())
            ->method('attachContentHeader');
        $header->expects($this->once())
            ->method('log')
            ->with('no swagger information found in request, skipping');

        $result = $header->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockResponse, $result);
    }

    public function testInvokePassesAlongResponseFromCallStack()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn(true);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponseModified = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) use ($mockResponseModified) {
            return $mockResponseModified;
        };

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'attachContentHeader',
                'log',
            ])
            ->getMock();
        $header->method('attachContentHeader')
            ->will($this->returnArgument(0));
        $header->expects($this->never())
            ->method('log');

        $result = $header->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockResponseModified, $result);
    }

    public function testInvokeAttachesContentHeadertoResponse()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn(true);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'attachContentHeader',
                'log',
            ])
            ->getMock();
        $header->expects($this->once())
            ->method('attachContentHeader')
            ->with($mockResponse)
            ->will($this->returnArgument(0));
        $header->expects($this->never())
            ->method('log');

        $header->__invoke($mockRequest, $mockResponse, $mockCallable);
    }
 
    public function testInvokeReturnsResponse()
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->method('getAttribute')
            ->with('swagger')
            ->willReturn(true);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockCallable = function ($request, $response) {
            return $response;
        };

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'attachContentHeader',
                'log',
            ])
            ->getMock();
        $header->method('attachContentHeader')
            ->will($this->returnArgument(0));
        $header->expects($this->never())
            ->method('log');

        $result = $header->__invoke($mockRequest, $mockResponse, $mockCallable);

        $this->assertSame($mockResponse, $result);
    }

    public function testAttachContentHeaderBailsIfAlreadySet()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('content-type')
            ->willReturn([
                'some value',
            ]);
        $mockResponse->expects($this->never())
            ->method('getBody');

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedAttachContentHeader = $reflectedHeader->getMethod('attachContentHeader');
        $reflectedAttachContentHeader->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isJsonContent',
            ])
            ->getMock();
        $header->expects($this->never())
            ->method('isJsonContent');

        $result = $reflectedAttachContentHeader->invokeArgs($header, [
            $mockResponse,
        ]);

        $this->assertSame($result, $mockResponse);
    }

    public function testAttachContentHeaderBailsIfNoBodyContent()
    {
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->expects($this->once())
            ->method('getSize')
            ->willReturn(null);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getHeader')
            ->willReturn([]);
        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStream);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedAttachContentHeader = $reflectedHeader->getMethod('attachContentHeader');
        $reflectedAttachContentHeader->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isJsonContent',
            ])
            ->getMock();
        $header->expects($this->never())
            ->method('isJsonContent');

        $result = $reflectedAttachContentHeader->invokeArgs($header, [
            $mockResponse,
        ]);

        $this->assertSame($result, $mockResponse);
    }

    public function testAttachContentHeaderAttachesJsonHeaderIfJsonContent()
    {
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('getSize')
            ->willReturn(1);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getHeader')
            ->willReturn([]);
        $mockResponse->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn($mockStream);
        $mockResponse->expects($this->once())
            ->method('withHeader')
            ->with('content-type', 'application/json')
            ->will($this->returnSelf());

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedAttachContentHeader = $reflectedHeader->getMethod('attachContentHeader');
        $reflectedAttachContentHeader->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isJsonContent',
            ])
            ->getMock();
        $header->expects($this->once())
            ->method('isJsonContent')
            ->with($mockStream)
            ->willReturn(true);

        $result = $reflectedAttachContentHeader->invokeArgs($header, [
            $mockResponse,
        ]);

        $this->assertSame($result, $mockResponse);
    }

    public function testAttachContentHeaderDefaultsToTextHeader()
    {
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('getSize')
            ->willReturn(1);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getHeader')
            ->willReturn([]);
        $mockResponse->method('getBody')
            ->willReturn($mockStream);
        $mockResponse->method('withHeader')
            ->with('content-type', 'text/plain')
            ->will($this->returnSelf());

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedAttachContentHeader = $reflectedHeader->getMethod('attachContentHeader');
        $reflectedAttachContentHeader->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'isJsonContent',
            ])
            ->getMock();
        $header->method('isJsonContent')
            ->with($mockStream)
            ->willReturn(false);

        $result = $reflectedAttachContentHeader->invokeArgs($header, [
            $mockResponse,
        ]);

        $this->assertSame($result, $mockResponse);
    }

    public function testIsJsonContentReturnsTrueIfJson()
    {
        $content = '{"key":"value"}';

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->expects($this->once())
            ->method('__toString')
            ->willReturn($content);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedIsJsonContent = $reflectedHeader->getMethod('isJsonContent');
        $reflectedIsJsonContent->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $reflectedIsJsonContent->invokeArgs($header, [
            $mockStream,
        ]);

        $this->assertTrue($result);
    }

    public function testIsJsonContentReturnsFalseIfNotJson()
    {
        $content = 'some text';

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->expects($this->once())
            ->method('__toString')
            ->willReturn($content);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedIsJsonContent = $reflectedHeader->getMethod('isJsonContent');
        $reflectedIsJsonContent->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $result = $reflectedIsJsonContent->invokeArgs($header, [
            $mockStream,
        ]);

        $this->assertFalse($result);
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
