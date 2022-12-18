<?php
declare(strict_types=1);
namespace Helhum\Topwire\ViewHelpers\Context;

use Helhum\Topwire\Context\TopwireContext;
use Helhum\Topwire\Context\TopwireContextFactory;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
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
     * @param RenderingContextInterface $renderingContext
     * @return TopwireContext
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): TopwireContext {
        $contextFactory = new TopwireContextFactory(
            $GLOBALS['TSFE']
        );
        return $contextFactory->forPath(
            renderingPath: $arguments['typoScriptPath'],
            contextRecordId: $arguments['tableName'] . ':' . $arguments['recordUid'],
        );
    }
}
