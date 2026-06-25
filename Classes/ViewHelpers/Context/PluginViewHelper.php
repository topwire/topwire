<?php
declare(strict_types=1);
namespace Topwire\ViewHelpers\Context;

use Topwire\Compatibility\ServerRequestFromRenderingContext;
use Topwire\Context\Attribute\Section;
use Topwire\Context\ContextStack;
use Topwire\Context\TopwireContextFactory;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class PluginViewHelper extends AbstractViewHelper
{
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

    public function __construct(private readonly ContextStack $contextStack)
    {
    }

    public function render(): string
    {
        assert($this->renderingContext !== null);
        $requestFromRenderingContext = new ServerRequestFromRenderingContext($this->renderingContext);
        $request = $requestFromRenderingContext->getRequest();

        $contextFactory = new TopwireContextFactory(
            $request
        );
        $context = $contextFactory->forArguments($this->arguments);
        if (isset($this->arguments['section'])) {
            $context = $context->withAttribute('section', new Section($this->arguments['section']));
        }
        $this->contextStack->push($context);
        $contentObject = $request->getAttribute('currentContentObject');
        $topwireRequest = $request->withAttribute('topwire', $context);
        $contentObject?->setRequest($topwireRequest);
        $requestFromRenderingContext->setRequest($topwireRequest);
        $renderedChildren = $this->renderChildren();
        $requestFromRenderingContext->setRequest($request);
        $contentObject?->setRequest($request);
        $this->contextStack->pop();

        return (string)$renderedChildren;
    }
}
