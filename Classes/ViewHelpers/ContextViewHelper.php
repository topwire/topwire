<?php
declare(strict_types=1);
namespace Topwire\ViewHelpers;

use Topwire\Compatibility\ServerRequestFromRenderingContext;
use Topwire\Context\ContextStack;
use Topwire\Context\TopwireContextFactory;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class ContextViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public const currentTopwireContext = 'currentTopwireContext';

    protected $escapeOutput = false;
    protected $escapeChildren = true;

    public function initializeArguments(): void
    {
        $this->registerArgument('typoScriptPath', 'string', 'Target Extension Name (without `tx_` prefix and no underscores). If NULL the current extension name is used', true);
        $this->registerArgument('recordUid', 'int', 'Uid of the record that will be passed to TypoScript. If not set, the current page uid will be used');
        $this->registerArgument('tableName', 'string', 'Table name of the record that will be passed to TypoScript. If not set, "pages" will be used', false, 'pages');
        $this->registerArgument('pageUid', 'int', 'Uid of the page, to which the context is bound to. If not set, the current page uid is used');
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
        $frontendController = (new ServerRequestFromRenderingContext($renderingContext))->getRequest()->getAttribute('frontend.controller');
        assert($frontendController instanceof TypoScriptFrontendController);
        $contextFactory = new TopwireContextFactory(
            $frontendController
        );
        $context = $contextFactory->forPath(
            renderingPath: $arguments['typoScriptPath'],
            contextRecordId: $arguments['tableName'] . ':' . $arguments['recordUid'],
        );
        $contextStack = new ContextStack($renderingContext->getViewHelperVariableContainer());
        $contextStack->push($context);
        $renderedChildren = $renderChildrenClosure();
        $contextStack->pop();

        return (string)$renderedChildren;
    }
}
