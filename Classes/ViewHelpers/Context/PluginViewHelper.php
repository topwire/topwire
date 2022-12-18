<?php
declare(strict_types=1);
namespace Helhum\Topwire\ViewHelpers\Context;

use Helhum\Topwire\RenderingContext\RenderingContext as TopwireRenderingContext;
use Helhum\Topwire\RenderingContext\RenderingContextFactory;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext as FluidRenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface as FluidRenderingContextInterface;
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
     * @param FluidRenderingContextInterface $renderingContext
     * @return TopwireRenderingContext
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        FluidRenderingContextInterface $renderingContext
    ): TopwireRenderingContext {
        return self::extractRenderingContext($arguments, $renderingContext);
    }

    /**
     * @param array<mixed> $arguments
     * @param FluidRenderingContextInterface $renderingContext
     * @return TopwireRenderingContext
     */
    private static function extractRenderingContext(
        array $arguments,
        FluidRenderingContextInterface $renderingContext
    ): TopwireRenderingContext {
        assert($renderingContext instanceof FluidRenderingContext);
        $extensionName = $arguments['extensionName'] ?? $renderingContext->getRequest();
        $pluginName = $arguments['pluginName'] ?? $renderingContext->getRequest();
        $renderingContextFactory = new RenderingContextFactory(
            $GLOBALS['TSFE']
        );
        return $renderingContextFactory->forPlugin(
            extensionName: $extensionName,
            pluginName: $pluginName,
            contextRecordId: null,
            pageUid: $arguments['pageUid']
        );
    }
}
