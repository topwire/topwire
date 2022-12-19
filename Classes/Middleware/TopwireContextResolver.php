<?php
declare(strict_types=1);
namespace Helhum\Topwire\Middleware;

use Helhum\Topwire\Context\TopwireContext;
use Helhum\Topwire\Turbo\FrameId;
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
        $contextString = $request->getQueryParams()[self::argumentName] ?? null;
        if (!$contextString && $request->hasHeader(self::turboHeader)) {
            $frameId = FrameId::fromHeaderString($request->getHeaderLine(self::turboHeader));
            $request = $request->withAttribute('turbo.frame', $frameId);
            $contextString = $frameId->context;
        }
        $pageArguments = $request->getAttribute('routing');
        if ($contextString === null || !$pageArguments instanceof PageArguments) {
            return $this->addVaryHeader($handler->handle($request));
        }
        $context = TopwireContext::fromJson($contextString);
        if ($context->contextRecord->pageId !== $pageArguments->getPageId()) {
            return $this->addVaryHeader($handler->handle($request));
        }
        $newStaticArguments = array_merge(
            $pageArguments->getStaticArguments(),
            [
                self::argumentName => $contextString,
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
