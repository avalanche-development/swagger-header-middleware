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

        $result = $next($request, $response);
        $result = $this->attachContentHeader($result);

        return $result;
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
     * @param string $message
     */
    protected function log($message)
    {
        $this->logger->debug("swagger-header-middleware: {$message}");
    }
}
