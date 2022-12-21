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
        $frameString = $request->getQueryParams()[self::argumentName] ?? $request->getHeaderLine(self::turboHeader);
        if (!empty($frameString)) {
            $frame = Frame::fromUntrustedString($frameString);
            $request = $request->withAttribute('turbo.frame', $frame);
        }
        $pageArguments = $request->getAttribute('routing');
        if ($frame?->context === null
            || !$pageArguments instanceof PageArguments
            || $frame->context->contextRecord->pageId !== $pageArguments->getPageId()
        ) {
            return $this->addVaryHeader($handler->handle($request));
        }
        $newStaticArguments = array_merge(
            $pageArguments->getStaticArguments(),
            [
                self::argumentName => $frame->cacheId,
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
            ->withAttribute('topwire', $frame->context)
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
