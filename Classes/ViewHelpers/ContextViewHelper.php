<?php
declare(strict_types=1);
namespace Topwire\ViewHelpers;

use Topwire\Compatibility\ServerRequestFromRenderingContext;
use Topwire\Context\ContextStack;
use Topwire\Context\TopwireContextFactory;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ContextViewHelper extends AbstractViewHelper
{
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

    public function render(): string
    {
        assert($this->renderingContext instanceof RenderingContext);
        $request = (new ServerRequestFromRenderingContext($this->renderingContext))->getRequest();
        $contextFactory = new TopwireContextFactory($request);
        $context = $contextFactory->forPath(
            renderingPath: $this->arguments['typoScriptPath'],
            contextRecordId: $this->arguments['tableName'] . ':' . $this->arguments['recordUid'],
            contextPageId: $this->arguments['pageUid'] ?? null,
        );
        $contextStack = new ContextStack($this->renderingContext->getViewHelperVariableContainer());
        $contextStack->push($context);
        $renderedChildren = $this->renderChildren();
        $contextStack->pop();

        return (string)$renderedChildren;
    }
}
