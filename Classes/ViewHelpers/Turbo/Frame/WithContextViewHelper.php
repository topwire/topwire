<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Telegraph\ViewHelpers\Turbo\Frame;

use Helhum\TYPO3\Telegraph\ContentObject\TelegraphContentObject;
use Helhum\TYPO3\Telegraph\RenderingContext\Exception\InvalidRenderingContext;
use Helhum\TYPO3\Telegraph\RenderingContext\RenderingContext as TelegraphRenderingContext;
use Helhum\TYPO3\Telegraph\Turbo\FrameOptions;
use Helhum\TYPO3\Telegraph\Turbo\FrameRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext as FluidRenderingContext;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface as FluidRenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class WithContextViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('id', 'string', 'id of the frame', true);
        $this->registerArgument('context', 'Helhum\\TYPO3\\Telegraph\\RenderingContext\\RenderingContext', 'Rendering context', true);
        $this->registerArgument('propagateUrl', 'bool', 'Whether the URL should be pushed to browser history', false, false);
        $this->registerArgument('async', 'bool', 'Whether HTML for the given context should be loaded asynchronously', false, false);
        $this->registerArgument('src', 'string', 'Override URL for async loading. Setting this will imply async.');
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
        if (!$arguments['context'] instanceof TelegraphRenderingContext) {
            throw new InvalidRenderingContext('"context" must be instance of RenderingContext', 1671280838);
        }
        $src = self::extractSourceUrl($arguments, $fluidRenderingContext);
        return (new FrameRenderer())->render(
            $arguments['context'],
            self::renderContent($src, $renderChildrenClosure, $arguments['context']),
            new FrameOptions(
                id: $arguments['id'],
                src: $src,
                propagateUrl: $arguments['propagateUrl'],
            )
        );
    }

    /**
     * @param array<mixed> $arguments
     * @param FluidRenderingContextInterface $fluidRenderingContext
     * @return string|null
     */
    private static function extractSourceUrl(array $arguments, FluidRenderingContextInterface $fluidRenderingContext): ?string
    {
        if (isset($arguments['src'])) {
            return $arguments['src'];
        }
        if ($arguments['async'] === true) {
            return null;
        }
        assert($fluidRenderingContext instanceof FluidRenderingContext);
        return $fluidRenderingContext->getUriBuilder()
            ->setTargetPageUid($arguments['context']->contextRecord->pageId)
            ->build();
    }

    private static function renderContent(?string $src, \Closure $renderChildrenClosure, TelegraphRenderingContext $renderingContext): string
    {
        if (isset($src)) {
            return $renderChildrenClosure();
        }
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        return $contentObjectRenderer
            ->cObjGetSingle(
                TelegraphContentObject::NAME,
                [
                    'context' => $renderingContext,
                ]
            );
    }
}
