<?php
declare(strict_types=1);
namespace Helhum\Topwire\Middleware;

use Helhum\Topwire\Context\ContextDenormalizer;
use Helhum\Topwire\Context\TopwireContext;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Routing\PageArguments;

class TopwireContextResolver implements MiddlewareInterface
{
    public function __construct(private readonly FrontendInterface $cache)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $context = null;
        $contextString = $request->getQueryParams()[TopwireContext::argumentName] ?? $request->getHeaderLine(TopwireContext::headerName);
        if (!empty($contextString)) {
            $context = TopwireContext::fromUntrustedString($contextString, new ContextDenormalizer());
        }
        $pageArguments = $request->getAttribute('routing');
        if ($context === null
            || !$pageArguments instanceof PageArguments
        ) {
            return $this->addVaryHeader($handler->handle($request->withAttribute('topwire', null)));
        }
        $cacheId = $context->cacheId;
        $frame = $context->getAttribute('frame');
        if ($context->contextRecord->pageId !== $pageArguments->getPageId()) {
            // Crossing page boundaries happen, when the controller returns a redirect response
            // In this case, the context is invalid, needs to be reset and a full page render must happen
            // Hotwire is smart enough in this case to convert the response to a full page visit,
            // when the target page does not contain a corresponding frame id.
            // This also allows for advanced setups, where the new target page actually contains the
            // frame id, e.g. for forms with a "thank you" element on a different page than the form.
            if (!$this->isPageBoundaryCrossingAllowed($context, $request)) {
                return $this->addVaryHeader($handler->handle($request->withAttribute('topwire', null)));
            }
            // Unset the context, because it is invalid on current page, but keep the validated frame
            // around, to allow elements on the target page with the same id show up, while keep them
            // hidden on a regular request.
            $context = null;
        }
        $newStaticArguments = array_merge(
            $pageArguments->getStaticArguments(),
            [
                TopwireContext::argumentName => $cacheId,
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
            ->withAttribute('topwireFrame', $frame)
        ;

        return $this->addVaryHeader($this->trackPageBoundaries($handler->handle($request), $context));
    }

    private function addVaryHeader(ResponseInterface $response): ResponseInterface
    {
        $varyHeader = $response->getHeader('Vary');
        $varyHeader[] = TopwireContext::headerName;
        return $response->withAddedHeader('Vary', $varyHeader);
    }

    private function isPageBoundaryCrossingAllowed(TopwireContext $context, RequestInterface $request): bool
    {
        if (Environment::getContext()->isDevelopment()) {
            return true;
        }
        $allowedUris = $this->cache->get('topwire_' . $context->contextRecord->pageId);
        $allowedUris = $allowedUris === false ? [] : $allowedUris;
        return isset($allowedUris[(string)$request->getUri()]);
    }

    private function trackPageBoundaries(ResponseInterface $response, ?TopwireContext $context): ResponseInterface
    {
        if ($context === null || $response->getHeaderLine('Location') === '') {
            return $response;
        }
        $cacheIdentifier = $this->getCacheIdentifier($context);
        $allowedUris = $this->cache->get('topwire_' . $context->contextRecord->pageId);
        $allowedUris = $allowedUris === false ? [] : $allowedUris;
        $allowedUris[$response->getHeaderLine('Location')] = true;
        $this->cache->set(
            $cacheIdentifier,
            $allowedUris,
            [
                'pageId_' . $context->contextRecord->pageId,
            ]
        );
        return $response;
    }

    private function getCacheIdentifier(TopwireContext $context): string
    {
        return 'topwire_' . $context->contextRecord->pageId;
    }
}
