<?php

namespace AvalancheDevelopment\SwaggerHeaderMiddleware;

use AvalancheDevelopment\Peel\HttpError;
use Psr\Http\Message\MessageInterface as Message;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Message\StreamInterface as Stream;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Header implements LoggerAwareInterface
{

    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger;
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @param callable $next
     * @return Response $response
     */
    public function __invoke(ServerRequest $request, Response $response, callable $next)
    {
        if (!$request->getAttribute('swagger')) {
            $this->log('no swagger information found in request, skipping');
            return $next($request, $response);
        }

        $consumeTypes = $request->getAttribute('swagger')['consumes'];
        if (!$this->checkIncomingContent($request, $consumeTypes)) {
            throw new HttpError\NotAcceptable('Unacceptable header was passed into this endpoint');
        }

        $result = $next($request, $response);
        $result = $this->attachContentHeader($result);

        $produceTypes = $request->getAttribute('swagger')['produces'];
        if (!$this->checkOutgoingContent($result, $produceTypes)) {
            throw new HttpError\InternalServerError('Invalid content detected');
        }
        if (!$this->checkAcceptHeader($request, $result)) {
            throw new HttpError\NotAcceptable('Unacceptable content detected');
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param array $consumeTypes
     * @return boolean
     */
    protected function checkIncomingContent(Request $request, array $consumeTypes)
    {
        if (!$request->getBody()->getSize()) {
            return true;
        }

        return $this->checkMessageContent($request, $consumeTypes);
    }

    /**
     * @param Message $message
     * @param array $acceptableTypes
     * @return boolean
     */
    protected function checkMessageContent(Message $message, array $acceptableTypes)
    {
        $contentType = $this->extractContentHeader($message);
        foreach ($acceptableTypes as $type) {
            if (in_array($type, $contentType)) {
                return true;
            }
            // todo wildcard mime type matching
        }

        return false;
    }

    /**
     * @param Message $message
     * @return array
     */
    protected function extractContentHeader(Message $message)
    {
        $contentHeaders = $message->getHeader('content-type');
        $contentHeaders = current($contentHeaders);
        $contentHeaders = explode(',', $contentHeaders);
        $contentHeaders = array_map(function ($entity) {
            $entity = explode(';', $entity);
            $entity = current($entity);
            return strtolower($entity);
        }, $contentHeaders);

        return $contentHeaders;
    }

    /**
     * @param Response $response
     * @return Response
     */
    protected function attachContentHeader(Response $response)
    {
        if (!empty($response->getHeader('content-type'))) {
            return $response;
        }

        if (!$response->getBody()->getSize()) {
            return $response;
        }

        $content = $response->getBody();
        if ($this->isJsonContent($content)) {
            $response = $response->withHeader('content-type', 'application/json');
            return $response;
        }

        $response = $response->withHeader('content-type', 'text/plain');
        return $response;
    }

    /**
     * @param Stream $content
     * @return boolean
     */
    protected function isJsonContent(Stream $content)
    {
        $data = (string) $content;
        $decodedData = json_decode($data);
        return (json_last_error() === JSON_ERROR_NONE) && ($decodedData !== null);
    }

    /**
     * @param Response $response
     * @param array $produceTypes
     * @return Response
     */
    protected function checkOutgoingContent(Response $response, array $produceTypes)
    {
        if (empty($response->getHeader('content-type'))) {
            return true;
        }

        return $this->checkMessageContent($response, $produceTypes);
    }

    /**
     * @param Request
     * @param Response
     * @return boolean
     */
    protected function checkAcceptHeader(Request $request, Response $response)
    {
        if (empty($request->getHeader('accept'))) {
            return true;
        }

        $acceptTypes = $request->getHeader('accept');
        return $this->checkMessageContent($response, $acceptTypes);
    }

    /**
     * @param string $message
     */
    protected function log($message)
    {
        $this->logger->debug("swagger-header-middleware: {$message}");
    }
}
