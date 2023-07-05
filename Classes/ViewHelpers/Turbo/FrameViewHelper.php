<?php
declare(strict_types=1);
namespace Topwire\ViewHelpers\Turbo;

use Topwire\Context\Attribute\Plugin;
use Topwire\Context\ContextStack;
use Topwire\Context\TopwireContext;
use Topwire\Turbo\Frame;
use Topwire\Turbo\FrameOptions;
use Topwire\Turbo\FrameRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
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
        $this->registerArgument('src', 'string', 'Either the keyword "async", which takes current Topwire context is taken into account, when fetching the HTML asynchronously. Alternatively can be set to any URL, for full flexibility.');
        $this->registerArgument('wrapResponse', 'bool', 'Whether to wrap the response of the content in this frame. Useful, for plugins or content, that isn\'t adapted to use Hotwire frames', false, false);
        $this->registerArgument('morph', 'bool', 'Whether the response HTML should be morphed instead of fully replaced', false, false);
        $this->registerArgument('propagateUrl', 'bool', 'Whether the URL should be pushed to browser history', false, false);
        $this->registerArgument('target', 'string', 'Turbo target for links and forms within this frame');
    }

    /**
     * @param array<mixed> $arguments
     * @throws \JsonException
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        assert($renderingContext instanceof RenderingContext);
        $stack = new ContextStack($renderingContext->getViewHelperVariableContainer());
        $context = $stack->current();
        $frame = new Frame(
            baseId: $arguments['id'],
            wrapResponse: $arguments['wrapResponse'],
            scope: $context?->scope,
        );
        if (isset($context)) {
            $context = $context->withAttribute('frame', $frame);
            $stack->push($context);
        }
        $content = $renderChildrenClosure();
        if (isset($context)) {
            $stack->pop();
        }
        if ($content === null) {
            return $frame->id;
        }
        return (new FrameRenderer())->render(
            frame: $frame,
            content: $content,
            options: new FrameOptions(
                src: self::extractSourceUrl($arguments, $renderingContext, $context),
                target: $arguments['target'],
                propagateUrl: $arguments['propagateUrl'],
                morph: $arguments['morph'],
            ),
            context: $context,
        );
    }

    /**
     * @param array<mixed> $arguments
     */
    private static function extractSourceUrl(array $arguments, RenderingContext $renderingContext, ?TopwireContext $context): ?string
    {
        if (!isset($context, $arguments['src'])) {
            return null;
        }
        if ($arguments['src'] !== 'async') {
            return $arguments['src'];
        }
        $arguments = [
            'topwire' => [
                'frameId' => $arguments['id'],
                'wrapResponse' => $arguments['wrapResponse'],
                'type' => 'typoScript',
                'typoScriptPath' => $context->renderingPath->jsonSerialize(),
                'recordUid' => $context->contextRecord->id,
                'tableName' => $context->contextRecord->tableName,
            ],
        ];
        $pluginAttribute = $context->getAttribute('plugin');
        if ($pluginAttribute instanceof Plugin && $pluginAttribute->actionName !== null) {
            $arguments[$pluginAttribute->pluginNamespace] = [
                'action' => $pluginAttribute->actionName,
            ];
        }
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return $uriBuilder
            ->reset()
            ->setTargetPageUid($context->contextRecord->pageId)
            ->setArguments($arguments)
            ->build();
    }
}
