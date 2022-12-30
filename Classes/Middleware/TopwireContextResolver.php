<?php
declare(strict_types=1);
namespace Helhum\Topwire\Middleware;

use Helhum\Topwire\Context\ContextDenormalizer;
use Helhum\Topwire\Context\TopwireContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Routing\PageArguments;

class TopwireContextResolver implements MiddlewareInterface
{
    private const topwireHeader = 'Topwire-Context';
    private const argumentName = 'tx_topwire';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $context = null;
        $contextString = $request->getQueryParams()[self::argumentName] ?? $request->getHeaderLine(self::topwireHeader);
        if (!empty($contextString)) {
            $context = TopwireContext::fromUntrustedString($contextString, new ContextDenormalizer());
        }
        $pageArguments = $request->getAttribute('routing');
        if ($context === null
            || !$pageArguments instanceof PageArguments
            || $context->contextRecord->pageId !== $pageArguments->getPageId()
        ) {
            return $this->addVaryHeader($handler->handle($request));
        }
        $newStaticArguments = array_merge(
            $pageArguments->getStaticArguments(),
            [
                self::argumentName => $context->cacheId,
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
        $varyHeader[] = self::topwireHeader;
        return $response->withAddedHeader('Vary', $varyHeader);
    }
}
