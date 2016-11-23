<?php

namespace AvalancheDevelopment\SwaggerHeaderMiddleware;

use AvalancheDevelopment\Peel\HttpError;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
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
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response $response
     */
    public function __invoke(Request $request, Response $response, callable $next)
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
        if (!$this->checkExpectHeader($request, $result)) {
            throw new HttpError\ExpectationFailed('Unexpected content detected');
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

        $contentHeaders = $this->extractContentHeader($request);
        foreach ($consumeTypes as $type) {
            if (in_array($type, $contentHeaders)) {
                return true;
            }
            // todo wildcard mime type matching
        }

        return false;
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function extractContentHeader(Request $request)
    {
        $contentHeaders = $request->getHeader('content');
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
        // if no content, quick bail
        // if content, test if json-ness
        // if json, attach json header
        // if not, attach text/plain
        return $response;
    }

    /**
     * @param Response $response
     * @param array $produceTypes
     * @return Response
     */
    protected function checkOutgoingContent(Response $response, array $produceTypes)
    {
        // if no content, quick bail
        // else, etc
        return true;
    }

    /**
     * @param Request
     * @param Response
     * @return boolean
     */
    protected function checkExpectHeader(Request $request, Response $response)
    {
        // if no content, quick bail
        // if no expectations, quick bail
        // else, etc
        return true;
    }

    /**
     * @param string $message
     */
    protected function log($message)
    {
        $this->logger->debug("swagger-header-middleware: {$message}");
    }
}
