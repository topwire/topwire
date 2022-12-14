<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Telegraph\Turbo\ViewHelpers;

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
        $this->registerArgument('renderingContext', 'string', 'rendering context', false, 'current');
        $this->registerArgument('propagateUrl', 'bool', 'Whether the URL should be pushed to browser history', false, false);
    }

    /**
     * @param array<mixed> $arguments
     * @param \Closure $renderChildrenClosure
     * @param FluidRenderingContextInterface $renderingContext
     * @return string
     * @throws \JsonException
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        FluidRenderingContextInterface $renderingContext
    ): string {
        return (new FrameRenderer())->render(
            self::extractRenderingContext($arguments, $renderingContext),
            $renderChildrenClosure(),
            new FrameOptions(
                id: $arguments['id'],
                propagateUrl: $arguments['propagateUrl'],
            )
        );
    }

    /**
     * @param array<mixed> $arguments
     * @param FluidRenderingContext $renderingContext
     * @return TelegraphRenderingContext|null
     */
    private static function extractRenderingContext(array $arguments, FluidRenderingContext $renderingContext): ?TelegraphRenderingContext
    {
        if ($arguments['renderingContext'] === 'none') {
            return null;
        }
        assert($renderingContext instanceof FluidRenderingContext);
        $renderingContextFactory = new RenderingContextFactory(
            $GLOBALS['TSFE']
        );
        return $renderingContextFactory->forExtbaseRequest(
            $renderingContext->getRequest(),
            GeneralUtility::makeInstance(ConfigurationManager::class),
        );
    }
}
