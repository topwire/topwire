<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Telegraph\ViewHelpers\Turbo;

use Helhum\TYPO3\Telegraph\RenderingContext\RenderingContext as TelegraphRenderingContext;
use Helhum\TYPO3\Telegraph\RenderingContext\RenderingContextFactory;
use Helhum\TYPO3\Telegraph\Turbo\FrameOptions;
use Helhum\TYPO3\Telegraph\Turbo\FrameRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext as FluidRenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface as FluidRenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class FrameViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('id', 'string', 'id of the frame', true);
        $this->registerArgument('propagateUrl', 'bool', 'Whether the URL should be pushed to browser history', false, false);
    }

    /**
     * @param array<mixed> $arguments
     * @param \Closure $renderChildrenClosure
     * @param FluidRenderingContextInterface $fluidRenderingContext
     * @return string
     * @throws \JsonException
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        FluidRenderingContextInterface $fluidRenderingContext
    ): string {
        $telegraphRenderingContext = self::extractRenderingContext($fluidRenderingContext);
        return (new FrameRenderer())->render(
            $telegraphRenderingContext,
            $renderChildrenClosure(),
            new FrameOptions(
                id: $arguments['id'],
                src: null,
                propagateUrl: $arguments['propagateUrl'],
            )
        );
    }

    private static function extractRenderingContext(FluidRenderingContextInterface $fluidRenderingContext): TelegraphRenderingContext
    {
        assert($fluidRenderingContext instanceof FluidRenderingContext);
        $renderingContextFactory = new RenderingContextFactory(
            $GLOBALS['TSFE']
        );
        return $renderingContextFactory->forExtbaseRequest(
            $fluidRenderingContext->getRequest(),
            GeneralUtility::makeInstance(ConfigurationManager::class),
        );
    }
}
