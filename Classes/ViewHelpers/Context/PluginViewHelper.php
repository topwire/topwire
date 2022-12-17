<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Telegraph\ViewHelpers\Context;

use Helhum\TYPO3\Telegraph\RenderingContext\RenderingContext as TelegraphRenderingContext;
use Helhum\TYPO3\Telegraph\RenderingContext\RenderingContextFactory;
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
     * @return TelegraphRenderingContext
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        FluidRenderingContextInterface $renderingContext
    ): TelegraphRenderingContext {
        return self::extractRenderingContext($arguments, $renderingContext);
    }

    /**
     * @param array<mixed> $arguments
     * @param FluidRenderingContextInterface $renderingContext
     * @return TelegraphRenderingContext
     */
    private static function extractRenderingContext(
        array $arguments,
        FluidRenderingContextInterface $renderingContext
    ): TelegraphRenderingContext {
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
