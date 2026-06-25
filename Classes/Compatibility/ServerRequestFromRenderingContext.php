<?php

declare(strict_types=1);

namespace Topwire\Compatibility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * @deprecated can be removed, when Compatibility to TYPO3 v12 is removed
 */
class ServerRequestFromRenderingContext
{
    private readonly RenderingContext $renderingContext;

    public function __construct(RenderingContextInterface $renderingContext)
    {
        assert($renderingContext instanceof RenderingContext);
        $this->renderingContext = $renderingContext;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->renderingContext->getAttribute(ServerRequestInterface::class);
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->renderingContext->setAttribute(ServerRequestInterface::class, $request);
    }
}
