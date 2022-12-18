<?php
declare(strict_types=1);
namespace Helhum\Topwire\ViewHelpers\Context;

use Helhum\Topwire\RenderingContext\RenderingContext as TopwireRenderingContext;
use Helhum\Topwire\RenderingContext\RenderingContextFactory;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface as FluidRenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class ContentElementViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('contentElementUid', 'int', 'Uid of the content element that will be rendered', true);
        $this->registerArgument('pageUid', 'int', 'Uid of the page, on which the content element will be rendered. If NULL the current page uid is used');
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
        $renderingContextFactory = new RenderingContextFactory(
            $GLOBALS['TSFE']
        );
        return $renderingContextFactory->forPath(
            renderingPath: 'tt_content',
            contextRecordId: 'tt_content:' . $arguments['contentElementUid'],
        );
    }
}
