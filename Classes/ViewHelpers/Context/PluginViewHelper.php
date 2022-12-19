<?php
declare(strict_types=1);
namespace Helhum\Topwire\ViewHelpers\Context;

use Helhum\Topwire\Context\TopwireContext;
use Helhum\Topwire\Context\TopwireContextFactory;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class PluginViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('extensionName', 'string', 'Target Extension Name (without `tx_` prefix and no underscores). If NULL the current extension name is used');
        $this->registerArgument('pluginName', 'string', 'Target plugin. If empty, the current plugin name is used');
        $this->registerArgument('pageUid', 'int', 'Uid of the page, on which the plugin will be rendered. If NULL the current page uid is used');
    }

    /**
     * @param array<mixed> $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return TopwireContext
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): TopwireContext {
        return self::extractTopwireContext($arguments, $renderingContext);
    }

    /**
     * @param array<mixed> $arguments
     * @param RenderingContextInterface $renderingContext
     * @return TopwireContext
     */
    private static function extractTopwireContext(
        array $arguments,
        RenderingContextInterface $renderingContext
    ): TopwireContext {
        assert($renderingContext instanceof RenderingContext);
        $frontendController = $renderingContext->getRequest()->getAttribute('frontend.controller');
        assert($frontendController instanceof TypoScriptFrontendController);
        $extensionName = $arguments['extensionName'] ?? $renderingContext->getRequest();
        $pluginName = $arguments['pluginName'] ?? $renderingContext->getRequest();
        $contextFactory = new TopwireContextFactory(
            $frontendController
        );
        return $contextFactory->forPlugin(
            extensionName: $extensionName,
            pluginName: $pluginName,
            contextRecordId: null,
            pageUid: $arguments['pageUid']
        );
    }
}
