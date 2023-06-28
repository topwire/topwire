<?php
declare(strict_types=1);
namespace Topwire\ViewHelpers\Context;

use Topwire\Context\Attribute\Section;
use Topwire\Context\ContextStack;
use Topwire\Context\TopwireContextFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
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
        $this->registerArgument('action', 'string', 'Target action. If empty, the current action is used. This is only relevant, when using the <topwire:context.render /> view helper as a child');
        $this->registerArgument('section', 'string', 'Fluid section to render only. If empty, the whole template is rendered. This is only relevant, when using the <topwire:context.render /> view helper as a child and the controller respects this information');
        $this->registerArgument('pageUid', 'int', 'Uid of the page, on which the plugin will be rendered. If empty, the current page uid is used');
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
        $request = $renderingContext->getRequest();
        $context = $contextFactory->forRequest($request, $arguments);
        if (isset($arguments['section'])) {
            $context = $context->withAttribute('section', new Section($arguments['section']));
        }
        $contextStack = new ContextStack($renderingContext->getViewHelperVariableContainer());
        $contextStack->push($context);
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $configurationManager->getContentObject()?->setRequest($request->withAttribute('topwire', $context));
        $renderingContext->setRequest($request->withAttribute('topwire', $context));
        $renderedChildren = $renderChildrenClosure();
        $renderingContext->setRequest($request);
        $configurationManager->getContentObject()?->setRequest($request);
        $contextStack->pop();

        return (string)$renderedChildren;
    }
}
