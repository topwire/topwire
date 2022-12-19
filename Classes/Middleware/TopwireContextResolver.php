<?php
declare(strict_types=1);
namespace Helhum\Topwire\Middleware;

use Helhum\Topwire\Turbo\Frame;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Routing\PageArguments;

class TopwireContextResolver implements MiddlewareInterface
{
    private const turboHeader = 'Turbo-Frame';
    private const argumentName = 'tx_topwire';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $frame = null;
        if ($request->hasHeader(self::turboHeader)) {
            $frame = Frame::fromUntrustedString($request->getHeaderLine(self::turboHeader));
            $request = $request->withAttribute('turbo.frame', $frame);
        } elseif (isset($request->getQueryParams()[self::argumentName])) {
            $frame = Frame::fromUntrustedString($request->getQueryParams()[self::argumentName]);
        }
        $context = $frame?->context;
        $cacheId = $frame?->cacheId;
        $pageArguments = $request->getAttribute('routing');
        if ($context === null
            || $cacheId === null
            || !$pageArguments instanceof PageArguments
            || $context->contextRecord->pageId !== $pageArguments->getPageId()
        ) {
            return $this->addVaryHeader($handler->handle($request));
        }
        $newStaticArguments = array_merge(
            $pageArguments->getStaticArguments(),
            [
                self::argumentName => $cacheId,
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
            ->withAttribute('topwire', $context)
        ;

        return $this->addVaryHeader($handler->handle($request));
    }

    private function addVaryHeader(ResponseInterface $response): ResponseInterface
    {
        $varyHeader = $response->getHeader('Vary');
        $varyHeader[] = self::turboHeader;
        return $response->withAddedHeader('Vary', $varyHeader);
    }
}
