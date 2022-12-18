<?php
declare(strict_types=1);
namespace Helhum\Topwire\ViewHelpers\Context;

use Helhum\Topwire\Context\TopwireContext;
use Helhum\Topwire\Context\TopwireContextFactory;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
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
            renderingPath: 'tt_content',
            contextRecordId: 'tt_content:' . $arguments['contentElementUid'],
        );
    }
}
