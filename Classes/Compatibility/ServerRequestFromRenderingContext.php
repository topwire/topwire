<?php

declare(strict_types=1);

namespace Topwire\Compatibility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext as FluidRenderingContext;

/**
 * @deprecated can be removed, when Compatibility to TYPO3 v12 is removed
 */
class ServerRequestFromRenderingContext
{
    private readonly RenderingContext $renderingContext;

    public function __construct(FluidRenderingContext $renderingContext)
    {
        assert($renderingContext instanceof RenderingContext);
        $this->renderingContext = $renderingContext;
    }

    public function getRequest(): ServerRequestInterface
    {
        if ((new Typo3Version())->getMajorVersion() < 13) {
            $request = $this->renderingContext->getRequest();
        } else {
            $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        }
        assert($request instanceof ServerRequestInterface);
        return $request;
    }
}
