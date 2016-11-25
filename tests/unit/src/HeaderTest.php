<?php

namespace AvalancheDevelopment\SwaggerHeaderMiddleware;

use PHPUnit_Framework_TestCase;
use Psr\Http\Message\MessageInterface;
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
                'checkMessageContent'
            ])
            ->getMock();
        $header->expects($this->never())
            ->method('checkMessageContent');

        $result = $reflectedCheckIncomingContent->invokeArgs($header, [
            $mockRequest,
            [],
        ]);

        $this->assertTrue($result);
    }

    public function testCheckIncomingContentPassesOnCheckerIfContainsBody()
    {
        $consumeTypes = [
            'application/vnd.github+json',
            'application/json',
        ];

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->method('getBody')
            ->willReturn($mockStream);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedCheckIncomingContent = $reflectedHeader->getMethod('checkIncomingContent');
        $reflectedCheckIncomingContent->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMessageContent'
            ])
            ->getMock();
        $header->expects($this->once())
            ->method('checkMessageContent')
            ->with($mockRequest, $consumeTypes)
            ->willReturn(true);

        $result = $reflectedCheckIncomingContent->invokeArgs($header, [
            $mockRequest,
            $consumeTypes,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckMessageContentReturnsTrueIfPassed()
    {
        $contentHeader = [
            'application/json',
        ];
        $consumeTypes = [
            'application/vnd.github+json',
            'application/json',
        ];

        $mockMessage = $this->createMock(MessageInterface::class);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedCheckMessageContent = $reflectedHeader->getMethod('checkMessageContent');
        $reflectedCheckMessageContent->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'extractContentHeader'
            ])
            ->getMock();
        $header->expects($this->once())
            ->method('extractContentHeader')
            ->with($mockMessage)
            ->willReturn($contentHeader);

        $result = $reflectedCheckMessageContent->invokeArgs($header, [
            $mockMessage,
            $consumeTypes,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckMessageContentReturnsFalseIfNotPassed()
    {
        $contentHeader = [];
        $consumeTypes = [
            'application/vnd.github+json',
            'application/json',
        ];

        $mockMessage = $this->createMock(MessageInterface::class);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedCheckMessageContent = $reflectedHeader->getMethod('checkMessageContent');
        $reflectedCheckMessageContent->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'extractContentHeader'
            ])
            ->getMock();
        $header->method('extractContentHeader')
            ->willReturn($contentHeader);

        $result = $reflectedCheckMessageContent->invokeArgs($header, [
            $mockMessage,
            $consumeTypes,
        ]);

        $this->assertFalse($result);
    }

    public function testCheckMessageContentReturnsFalseIfNoMatch()
    {
        $contentHeader = [
            'text/plain',
        ];
        $consumeTypes = [
            'application/vnd.github+json',
            'application/json',
        ];

        $mockMessage = $this->createMock(MessageInterface::class);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedCheckMessageContent = $reflectedHeader->getMethod('checkMessageContent');
        $reflectedCheckMessageContent->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'extractContentHeader'
            ])
            ->getMock();
        $header->method('extractContentHeader')
            ->willReturn($contentHeader);

        $result = $reflectedCheckMessageContent->invokeArgs($header, [
            $mockMessage,
            $consumeTypes,
        ]);

        $this->assertFalse($result);
    }

    public function testExtractContentHeaderHandlesMultipleTypes()
    {
        $contentTypes = 'application/vnd.github+json, application/json';
        $contentTypeCount = 2;

        $mockMessage = $this->createMock(MessageInterface::class);
        $mockMessage->expects($this->once())
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
            $mockMessage,
        ]);

        $this->assertCount($contentTypeCount, $result);
    }

    public function testExtractContentHeaderParsesOutOptions()
    {
        $contentType = 'text/plain; charset=utf8';
        $extractedContentHeader = [
            'text/plain',
        ];

        $mockMessage = $this->createMock(MessageInterface::class);
        $mockMessage->expects($this->once())
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
            $mockMessage,
        ]);

        $this->assertEquals($extractedContentHeader, $result);
    }

    public function testExtractContentHeaderLowersCasing()
    {
        $contentType = 'Application/Json';
        $casedContentHeader = [
            'application/json',
        ];

        $mockMessage = $this->createMock(MessageInterface::class);
        $mockMessage->expects($this->once())
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
            $mockMessage,
        ]);

        $this->assertEquals($casedContentHeader, $result);
    }

    public function testAttachContentHeaderBailsIfAlreadySet()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('content')
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
            ->with('content', 'application/json')
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
            ->with('content', 'text/plain')
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

    public function testCheckOutgoingContentReturnsTrueIfEmptyHeader()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('content')
            ->willReturn([]);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedCheckOutgoingContent = $reflectedHeader->getMethod('checkOutgoingContent');
        $reflectedCheckOutgoingContent->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMessageContent'
            ])
            ->getMock();
        $header->expects($this->never())
            ->method('checkMessageContent');

        $result = $reflectedCheckOutgoingContent->invokeArgs($header, [
            $mockResponse,
            [],
        ]);

        $this->assertTrue($result);
    }

    public function testCheckOutgoingContentPassesOnCheckerIfContainsHeaders()
    {
        $produceTypes = [
            'application/json',
        ];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('content')
            ->willReturn([
                'some value',
            ]);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedCheckOutgoingContent = $reflectedHeader->getMethod('checkOutgoingContent');
        $reflectedCheckOutgoingContent->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMessageContent'
            ])
            ->getMock();
        $header->expects($this->once())
            ->method('checkMessageContent')
            ->with($mockResponse, $produceTypes)
            ->willReturn(true);

        $result = $reflectedCheckOutgoingContent->invokeArgs($header, [
            $mockResponse,
            $produceTypes,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckExpectHeaderReturnsTrueIfEmptyHeader()
    {
        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->once())
            ->method('getHeader')
            ->with('expect')
            ->willReturn([]);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedCheckExpectHeader = $reflectedHeader->getMethod('checkExpectHeader');
        $reflectedCheckExpectHeader->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMessageContent'
            ])
            ->getMock();
        $header->expects($this->never())
            ->method('checkMessageContent');

        $result = $reflectedCheckExpectHeader->invokeArgs($header, [
            $mockRequest,
            $mockResponse,
        ]);

        $this->assertTrue($result);
    }

    public function testCheckExpectHeaderPassesOnCheckerIfContainsHeaders()
    {
        $expectTypes = [
            'application/json',
        ];

        $mockRequest = $this->createMock(RequestInterface::class);
        $mockRequest->expects($this->exactly(2))
            ->method('getHeader')
            ->with('expect')
            ->willReturn($expectTypes);

        $mockResponse = $this->createMock(ResponseInterface::class);

        $reflectedHeader = new ReflectionClass(Header::class);
        $reflectedCheckExpectHeader = $reflectedHeader->getMethod('checkExpectHeader');
        $reflectedCheckExpectHeader->setAccessible(true);

        $header = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkMessageContent'
            ])
            ->getMock();
        $header->expects($this->once())
            ->method('checkMessageContent')
            ->with($mockResponse, $expectTypes)
            ->willReturn(true);

        $result = $reflectedCheckExpectHeader->invokeArgs($header, [
            $mockRequest,
            $mockResponse,
        ]);

        $this->assertTrue($result);
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
