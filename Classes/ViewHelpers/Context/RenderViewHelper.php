<?php
declare(strict_types=1);
namespace Topwire\ViewHelpers\Context;

use Psr\Http\Message\ServerRequestInterface;
use Topwire\ContentObject\TopwireContentObject;
use Topwire\Context\Attribute\Plugin;
use Topwire\Context\ContextStack;
use Topwire\Context\Exception\InvalidTopwireContext;
use Topwire\Context\TopwireContext;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class RenderViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    /**
     * @param array<mixed> $arguments
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
        $request = $renderingContext->getRequest()?->withAttribute('topwire', $context);
        assert($request instanceof ServerRequestInterface);
        $actionRequest = self::addActionNameToRequest(
            $request,
            $context,
        );
        $contentData = [];
        if ($request !== $actionRequest) {
            $contentData = [
                'topwire' => [
                    'context' => $context,
                    'routing' => $actionRequest->getAttribute('routing'),
                ]
            ];
        }
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->start(
            $contentData,
            $context->contextRecord->tableName,
            $actionRequest
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

    private static function addActionNameToRequest(ServerRequestInterface $request, TopwireContext $context): ServerRequestInterface
    {
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
        $extbaseArguments = [];
        if ($request instanceof Request) {
            $extbaseArguments = $request->getArguments();
        }
        $newRootArguments = array_merge(
            $pageArguments->getRouteArguments(),
            [
                $plugin->pluginNamespace => array_replace(
                    $extbaseArguments,
                    [
                        'action' => $plugin->actionName,
                    ]
                ),
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
