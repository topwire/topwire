<?php
declare(strict_types=1);
namespace Topwire\ViewHelpers\Context;

use Psr\Http\Message\ServerRequestInterface;
use Topwire\Compatibility\ServerRequestFromRenderingContext;
use Topwire\ContentObject\TopwireContentObject;
use Topwire\Context\Attribute\Plugin;
use Topwire\Context\ContextStack;
use Topwire\Context\Exception\InvalidTopwireContext;
use Topwire\Context\TopwireContext;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class RenderViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function __construct(private readonly ContextStack $contextStack)
    {
    }

    /**
     * @throws InvalidTopwireContext
     */
    public function render(): string
    {
        $context = $this->contextStack->current();
        if (!$context instanceof TopwireContext) {
            throw new InvalidTopwireContext('Can only render as child of a Topwire context view helper', 1671623956);
        }
        assert($this->renderingContext instanceof RenderingContext);
        $request = (new ServerRequestFromRenderingContext($this->renderingContext))->getRequest()->withAttribute('topwire', $context);
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
        );
        $contentObjectRenderer->setRequest($actionRequest);
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

        $routeArguments = $pageArguments->getRouteArguments();
        $routeArguments[$plugin->pluginNamespace] ??= [];
        assert(is_array($routeArguments[$plugin->pluginNamespace]));
        $routeArguments[$plugin->pluginNamespace]['action'] = $plugin->actionName;

        $modifiedPageArguments = new PageArguments(
            $pageArguments->getPageId(),
            $pageArguments->getPageType(),
            $routeArguments,
            $pageArguments->getStaticArguments(),
            $pageArguments->getDynamicArguments()
        );
        return $request->withAttribute('routing', $modifiedPageArguments);
    }
}
