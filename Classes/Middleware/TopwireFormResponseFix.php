<?php
namespace Topwire\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Lightweight alternative to regular frontend requests, rendering only the provided context record/ plugin
 */
class TopwireFormResponseFix implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if ($request->getMethod() !== 'POST'
            || $response->hasHeader('Location')
            || $request->hasHeader('Turbo-Frame')
            || !str_contains($request->getHeaderLine('Accept'), 'text/vnd.turbo-stream.html')
        ) {
            return $response;
        }
        return $response->withStatus(422, 'Form invalid');
    }
}
