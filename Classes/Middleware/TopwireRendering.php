<?php
namespace Topwire\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Topwire\Exception\InvalidContentType;

/**
 * Lightweight alternative to regular frontend requests, rendering only the provided context record/ plugin
 */
class TopwireRendering implements MiddlewareInterface
{
    private const defaultContentType = 'text/html';
    private const turboStreamContentType = 'text/vnd.turbo-stream.html';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $contentTypeHeader = $response->getHeaderLine('Content-Type');
        $isStreamResponseAllowed = str_contains($request->getHeaderLine('Accept'), self::turboStreamContentType);
        if ($contentTypeHeader === ''
            || $response->getStatusCode() !== 200
            || !$request->hasHeader('Turbo-Frame')
            || str_starts_with($contentTypeHeader, self::defaultContentType)
            || ($isStreamResponseAllowed && str_starts_with($contentTypeHeader, self::turboStreamContentType))
        ) {
            return $response;
        }
        throw new InvalidContentType(
            sprintf(
                'Turbo frame requests must return content/type "%s"%s, got "%s". '
                . 'Maybe forgot to add data-turbo="false" attribute for links leading to this error? '
                . 'Alternatively you can rewrite the current URL to have a file extension',
                self::defaultContentType,
                $isStreamResponseAllowed ? sprintf(' or "%s"', self::turboStreamContentType) : '',
                $contentTypeHeader
            ),
            1671308188
        );
    }
}
