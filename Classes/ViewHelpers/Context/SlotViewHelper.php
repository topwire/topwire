<?php
declare(strict_types=1);
namespace Helhum\Topwire\ViewHelpers\Context;

use Helhum\Topwire\ContentObject\TopwireContentObject;
use Helhum\Topwire\Context\Attribute\Plugin;
use Helhum\Topwire\Context\ContextStack;
use Helhum\Topwire\Context\Exception\InvalidTopwireContext;
use Helhum\Topwire\Context\TopwireContext;
use Helhum\Topwire\Turbo\Frame;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class SlotViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('frame', 'string', 'Frame id of a frame (or section name of a template section), that should be rendered only. If empty, the whole template is rendered');
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
        $context = (new ContextStack($renderingContext->getViewHelperVariableContainer()))->current();
        if (!$context instanceof TopwireContext) {
            throw new InvalidTopwireContext('Can only render as child of a Topwire context view helper', 1671623956);
        }
        assert($renderingContext instanceof RenderingContext);
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->start(
            [],
            $context->contextRecord->tableName,
            self::addActionNameToRequest(
                $renderingContext->getRequest()
                    ->withAttribute('topwire', $context),
                $arguments
            )
        );
        $contentObjectRenderer->currentRecord = $context->contextRecord->tableName . ':' . $context->contextRecord->id;
        return $contentObjectRenderer
            ->cObjGetSingle(
                TopwireContentObject::NAME,
                [
                    'context' => $context,
                ]
            );
    }

    /**
     * @param ServerRequestInterface $request
     * @param array<string, mixed> $arguments
     * @return ServerRequestInterface
     */
    private static function addActionNameToRequest(ServerRequestInterface $request, array $arguments): ServerRequestInterface
    {
        $context = $request->getAttribute('topwire');
        assert($context instanceof TopwireContext);
        if (isset($arguments['frame'])) {
            if ($context->getAttribute('frame') !== null) {
                throw new InvalidNestingOverride('Can not override frame of a slot that is already wrapped in a frame', 1672438241);
            }
            $context = $context->withAttribute('frame', new Frame($arguments['frame'], false, null));
            $request = $request->withAttribute('topwire', $context);
        }
        $plugin = $context->getAttribute('plugin');
        if (!$plugin instanceof Plugin
            || $plugin->actionName === null
        ) {
            return $request;
        }
        $pageArguments = $request->getAttribute('routing');
        if (!$pageArguments instanceof PageArguments) {
            return $request;
        }

        $newRootArguments = array_merge(
            $pageArguments->getRouteArguments(),
            [
                $plugin->pluginNamespace => [
                    'action' => $plugin->actionName,
                ],
            ]
        );
        $modifiedPageArguments = new PageArguments(
            $pageArguments->getPageId(),
            $pageArguments->getPageType(),
            $newRootArguments,
            $pageArguments->getStaticArguments(),
            $pageArguments->getDynamicArguments()
        );
        return $request->withAttribute('routing', $modifiedPageArguments);
    }
}
