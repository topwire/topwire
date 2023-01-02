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
            || $context->contextRecord->pageId !== $pageArguments->getPageId()
        ) {
            // Crossing page boundaries happen, when the controller returns a redirect response
            // In this case, the context is invalid, needs to be reset and a full page render must happen
            // Hotwire is smart enough in this case to convert the response to a full page visit,
            // when the target page does not contain a corresponding frame id.
            // This also allows for advanced setups, where the new target page actually contains the
            // frame id, e.g. for forms with a "thank you" element on a different page than the form.
            return $this->addVaryHeader($handler->handle($request->withAttribute('topwire', null)));
        }
        $newStaticArguments = array_merge(
            $pageArguments->getStaticArguments(),
            [
                TopwireContext::argumentName => $context->cacheId,
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
        $varyHeader[] = TopwireContext::headerName;
        return $response->withAddedHeader('Vary', $varyHeader);
    }
}
