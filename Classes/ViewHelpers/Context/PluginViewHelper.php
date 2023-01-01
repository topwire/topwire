<?php
declare(strict_types=1);
namespace Helhum\Topwire\ViewHelpers\Context;

use Helhum\Topwire\Context\Attribute\Section;
use Helhum\Topwire\Context\ContextStack;
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
    protected $escapeChildren = true;

    public function initializeArguments(): void
    {
        $this->registerArgument('extensionName', 'string', 'Target Extension Name (without `tx_` prefix and no underscores). If empty, the current extension name is used');
        $this->registerArgument('pluginName', 'string', 'Target plugin. If empty, the current plugin name is used');
        $this->registerArgument('action', 'string', 'Target action. If empty, the current action is used. This is only relevant, when using the <topwire:context.slot /> view helper as a child');
        $this->registerArgument('section', 'string', 'Fluid section to render only. If empty, the whole template is rendered. This is only relevant, when using the <topwire:context.slot /> view helper as a child and the controller respects this information');
        $this->registerArgument('pageUid', 'int', 'Uid of the page, on which the plugin will be rendered. If empty, the current page uid is used');
    }

    /**
     * @param array<mixed> $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
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
        $context = $contextFactory->forRequest($renderingContext->getRequest(), $arguments);
        if (isset($arguments['section'])) {
            $context = $context->withAttribute('section', new Section($arguments['section']));
        }
        $contextStack = new ContextStack($renderingContext->getViewHelperVariableContainer());
        $contextStack->push($context);
        $renderedChildren = $renderChildrenClosure();
        $contextStack->pop();

        return (string)$renderedChildren;
    }
}
