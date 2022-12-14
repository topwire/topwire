<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Telegraph\Middleware;

use Helhum\TYPO3\Telegraph\RenderingContext\RenderingContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Routing\PageArguments;

class RenderingContextResolver implements MiddlewareInterface
{
    private const contextHeader = 'Telegraph-Rendering-Context';
    private const argumentNamespace = 'tx_telegraph';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pageArguments = $request->getAttribute('routing');
        if (
            !$pageArguments instanceof PageArguments
            || (
                !$request->hasHeader(self::contextHeader)
                && !isset($request->getQueryParams()[self::argumentNamespace])
            )
        ) {
            return $this->addVaryHeader($handler->handle($request));
        }
        $renderingContextString = $this->resolveContextString($request);
        $renderingContext = RenderingContext::fromJson($renderingContextString);
        if ($renderingContext->contextRecord->pageId !== $pageArguments->getPageId()) {
            return $this->addVaryHeader($handler->handle($request));
        }
        $newStaticArguments = array_merge(
            $pageArguments->getStaticArguments(),
            [
                self::argumentNamespace => $renderingContextString,
            ]
        );
        $modifiedPageArguments = new PageArguments(
            $pageArguments->getPageId(),
            $pageArguments->getPageType(),
            $pageArguments->getRouteArguments(),
            $newStaticArguments,
            $pageArguments->getDynamicArguments()
        );
        $request = $request
            ->withAttribute('routing', $modifiedPageArguments)
            ->withAttribute('telegraph', $renderingContext)
        ;

        return $this->addVaryHeader($handler->handle($request));
    }

    private function resolveContextString(ServerRequestInterface $request): string
    {
        return $request->getQueryParams()[self::argumentNamespace] ?? $request->getHeader(self::contextHeader)[0];
    }

    private function addVaryHeader(ResponseInterface $response): ResponseInterface
    {
        $varyHeader = $response->getHeader('Vary');
        $varyHeader[] = 'Telegraph-Rendering-Context';
        return $response->withAddedHeader('Vary', $varyHeader);
    }
}
