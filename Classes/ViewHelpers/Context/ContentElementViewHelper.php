<?php
declare(strict_types=1);
namespace Topwire\ViewHelpers\Context;

use Topwire\Context\ContextStack;
use Topwire\Context\TopwireContextFactory;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class ContentElementViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;
    protected $escapeChildren = true;

    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'Uid of the content element that will be rendered', true);
        $this->registerArgument('pageUid', 'int', 'Uid of the page, on which the content element will be rendered. If NULL the current page uid is used');
    }

    /**
     * @param array<mixed> $arguments
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        assert($renderingContext instanceof RenderingContext);
        $frontendController = $renderingContext->getRequest()->getAttribute('frontend.controller');
        assert($frontendController instanceof TypoScriptFrontendController);
        $contextFactory = new TopwireContextFactory(
            $frontendController
        );
        $context = $contextFactory->forPath(
            renderingPath: 'tt_content',
            contextRecordId: 'tt_content:' . $arguments['uid'],
        );
        $contextStack = new ContextStack($renderingContext->getViewHelperVariableContainer());
        $contextStack->push($context);
        $renderedChildren = $renderChildrenClosure();
        $contextStack->pop();

        return (string)$renderedChildren;
    }
}
