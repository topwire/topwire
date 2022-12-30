<?php
declare(strict_types=1);
namespace Helhum\Topwire\ViewHelpers\Turbo;

use Helhum\Topwire\Context\ContextStack;
use Helhum\Topwire\Context\TopwireContext;
use Helhum\Topwire\Context\TopwireContextFactory;
use Helhum\Topwire\Turbo\Frame;
use Helhum\Topwire\Turbo\FrameOptions;
use Helhum\Topwire\Turbo\FrameRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
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
        assert($renderingContext instanceof RenderingContext);
        $stack = new ContextStack($renderingContext->getViewHelperVariableContainer());
        [$wrapResponse, $context] = self::extractTopwireContext($renderingContext, $stack);
        $frame = new Frame(
            baseId: $arguments['id'],
            wrapResponse: $wrapResponse,
            context: $context,
        );
        $contextWithFrame = $context->withAttribute('frame', $frame);
        $stack->push($contextWithFrame);
        $content = $renderChildrenClosure();
        $stack->pop();
        if ($content === null) {
            return $frame->id;
        }
        return (new FrameRenderer())->render(
            frame: $frame,
            content: $content,
            options: new FrameOptions(
                src: self::extractSourceUrl($arguments, $renderingContext, $contextWithFrame),
                propagateUrl: $arguments['propagateUrl'],
            ),
            context: $contextWithFrame,
        );
    }

    /**
     * @param ContextStack $stack
     * @return array{0: bool, 1: TopwireContext}
     */
    private static function extractTopwireContext(RenderingContext $renderingContext, ContextStack $stack): array
    {
        if ($stack->current() instanceof TopwireContext) {
            return [true, $stack->current()];
        }
        $frontendController = $renderingContext->getRequest()->getAttribute('frontend.controller');
        assert($frontendController instanceof TypoScriptFrontendController);
        $contextFactory = new TopwireContextFactory(
            $frontendController
        );
        // @todo: check how this behaves in a standalone view
        return [
            false,
            $contextFactory->forExtbaseRequest(
                $renderingContext->getRequest(),
                GeneralUtility::makeInstance(ConfigurationManager::class),
            ),
        ];
    }

    /**
     * @param array<mixed> $arguments
     * @param RenderingContext $renderingContext
     * @param TopwireContext $context
     * @return string|null
     */
    private static function extractSourceUrl(array $arguments, RenderingContext $renderingContext, TopwireContext $context): ?string
    {
        if (!isset($arguments['src'])) {
            return null;
        }
        if ($arguments['src'] === 'async') {
            return $renderingContext->getUriBuilder()
                ->setTargetPageUid($context->contextRecord->pageId)
                ->setArguments([
                    'tx_topwire' => [
                        'frameId' => $arguments['id'],
                        'type' => 'typoScript',
                        'typoScriptPath' => $context->renderingPath->jsonSerialize(),
                        'recordUid' => $context->contextRecord->id,
                        'tableName' => $context->contextRecord->tableName,
                    ],
                ])
                ->build();
        }
        return $arguments['src'];
    }
}
