<?php
declare(strict_types=1);
namespace Helhum\Topwire\ViewHelpers\Turbo\Frame;

use Helhum\Topwire\ContentObject\TopwireContentObject;
use Helhum\Topwire\Context\Exception\InvalidTopwireContext;
use Helhum\Topwire\Context\TopwireContext;
use Helhum\Topwire\Turbo\Frame;
use Helhum\Topwire\Turbo\FrameOptions;
use Helhum\Topwire\Turbo\FrameRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class WithContextViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('id', 'string', 'id of the frame', true);
        $this->registerArgument('context', 'Helhum\\Topwire\\Context\\TopwireContext', 'Rendering context', true);
        $this->registerArgument('propagateUrl', 'bool', 'Whether the URL should be pushed to browser history', false, false);
        $this->registerArgument('async', 'bool', 'Whether HTML for the given context should be loaded asynchronously', false, false);
        $this->registerArgument('src', 'string', 'Override URL for async loading. Setting this will imply async.');
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
        if (!$arguments['context'] instanceof TopwireContext) {
            throw new InvalidTopwireContext('"context" must be instance of TopwireContext', 1671280838);
        }
        $src = self::extractSourceUrl($arguments, $renderingContext);
        return (new FrameRenderer())->render(
            frame: new Frame(
                baseId: $arguments['id'],
                context: $arguments['context'],
                wrapResponse: true,
            ),
            content: self::renderContent($src, $renderChildrenClosure, $arguments['context']),
            options: new FrameOptions(
                src: $src,
                propagateUrl: $arguments['propagateUrl'],
            ),
        );
    }

    /**
     * @param array<mixed> $arguments
     * @param RenderingContextInterface $renderingContext
     * @return string|null
     */
    private static function extractSourceUrl(array $arguments, RenderingContextInterface $renderingContext): ?string
    {
        if (isset($arguments['src'])) {
            return $arguments['src'];
        }
        if ($arguments['async'] === false) {
            return null;
        }
        assert($renderingContext instanceof RenderingContext);
        return $renderingContext->getUriBuilder()
            ->setTargetPageUid($arguments['context']->contextRecord->pageId)
            ->build();
    }

    private static function renderContent(?string $src, \Closure $renderChildrenClosure, TopwireContext $context): string
    {
        if (isset($src)) {
            return (string)$renderChildrenClosure();
        }
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        return $contentObjectRenderer
            ->cObjGetSingle(
                TopwireContentObject::NAME,
                [
                    'context' => $context,
                ]
            );
    }
}
