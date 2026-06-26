<?php
declare(strict_types=1);
namespace Topwire\ViewHelpers\Context;

use Psr\Http\Message\ServerRequestInterface;
use Topwire\Context\ContextStack;
use Topwire\Context\TopwireContextFactory;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ContentElementViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;
    protected $escapeChildren = true;

    public function __construct(private readonly ContextStack $contextStack)
    {
    }

    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'Uid of the content element that will be rendered', true);
        $this->registerArgument('pageUid', 'int', 'Uid of the page, on which the content element will be rendered. If NULL the current page uid is used');
    }

    public function render(): string
    {
        assert($this->renderingContext instanceof RenderingContext);

        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        $contextFactory = new TopwireContextFactory($request);
        $context = $contextFactory->forPath(
            renderingPath: 'tt_content',
            contextRecordId: 'tt_content:' . $this->arguments['uid'],
            contextPageId: $this->arguments['pageUid'] === null ? null : (int)$this->arguments['pageUid'],
        );
        $this->contextStack->push($context);
        $renderedChildren = $this->renderChildren();
        $this->contextStack->pop();

        return (string)$renderedChildren;
    }
}
