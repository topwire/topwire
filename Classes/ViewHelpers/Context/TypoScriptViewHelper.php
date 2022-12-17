<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Telegraph\ViewHelpers\Context;

use Helhum\TYPO3\Telegraph\RenderingContext\RenderingContext as TelegraphRenderingContext;
use Helhum\TYPO3\Telegraph\RenderingContext\RenderingContextFactory;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface as FluidRenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class TypoScriptViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('typoScriptPath', 'string', 'Target Extension Name (without `tx_` prefix and no underscores). If NULL the current extension name is used', true);
        $this->registerArgument('recordUid', 'int', 'Uid of the record that will be passed to TypoScript. If not set, the current page uid will be used');
        $this->registerArgument('tableName', 'string', 'Table name of the record that will be passed to TypoScript. If not set, "pages" will be used', false, 'pages');
        $this->registerArgument('pageUid', 'int', 'Uid of the page, on which the content element will be rendered. If not set, the current page uid is used');
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
        $renderingContextFactory = new RenderingContextFactory(
            $GLOBALS['TSFE']
        );
        return $renderingContextFactory->forPath(
            renderingPath: $arguments['typoScriptPath'],
            contextRecordId: $arguments['tableName'] . ':' . $arguments['recordUid'],
        );
    }
}
