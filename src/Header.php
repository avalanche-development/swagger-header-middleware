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
        if (!$this->checkIncomingHeader($request, $consumeTypes)) {
            throw new HttpError\NotAcceptable('Unacceptable header was passed into this endpoint');
        }

        $result = $next($request, $response);

        // step three - format as json if array
        // step four - bail if json ain't right (500)
        // step five - attach outbound header
        // step six - bail if header is unacceptable (417)

        return $result;
    }

    /**
     * @param Request $request
     * @param array $consumeTypes
     * @return boolean
     */
    protected function checkIncomingHeader(Request $request, $consumeTypes)
    {
        $contentHeaders = $this->extractContentHeader($request);

        foreach ($consumeTypes as $type) {
            if (in_array($type, $contentHeaders)) {
                return true;
            }
            // todo wildcard content matching
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
     * @param string $message
     */
    protected function log($message)
    {
        $this->logger->debug("swagger-header-middleware: {$message}");
    }
}
