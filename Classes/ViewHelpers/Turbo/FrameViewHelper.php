<?php
declare(strict_types=1);
namespace Helhum\Topwire\ViewHelpers\Turbo;

use Helhum\Topwire\Context\TopwireContext;
use Helhum\Topwire\Context\TopwireContextFactory;
use Helhum\Topwire\Turbo\FrameOptions;
use Helhum\Topwire\Turbo\FrameRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
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
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @throws \JsonException
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $context = self::extractTopwireContext($renderingContext);
        return (new FrameRenderer())->render(
            $context,
            $renderChildrenClosure(),
            new FrameOptions(
                id: $arguments['id'],
                src: null,
                propagateUrl: $arguments['propagateUrl'],
            )
        );
    }

    private static function extractTopwireContext(RenderingContextInterface $renderingContext): TopwireContext
    {
        assert($renderingContext instanceof RenderingContext);
        $contextFactory = new TopwireContextFactory(
            $GLOBALS['TSFE']
        );
        return $contextFactory->forExtbaseRequest(
            $renderingContext->getRequest(),
            GeneralUtility::makeInstance(ConfigurationManager::class),
        );
    }
}
