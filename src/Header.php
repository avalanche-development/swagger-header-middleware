<?php

namespace AvalancheDevelopment\SwaggerHeaderMiddleware;

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

        // step one - validate header of incoming request

        $result = $next($request, $response);

        // step three - format as json if array
        // step four - complain if json ain't right
        // step five - attach outbound header

        return $result;
    }

    /**
     * @param string $message
     */
    private function log($message)
    {
        $this->logger->debug("swagger-header-middleware: {$message}");
    }
}
