<?php
declare(strict_types=1);
namespace Topwire\ViewHelpers\Turbo;

use Psr\Http\Message\ServerRequestInterface;
use Topwire\Compatibility\ServerRequestFromRenderingContext;
use Topwire\Context\Attribute\Plugin;
use Topwire\Context\ContextStack;
use Topwire\Context\TopwireContext;
use Topwire\Turbo\Frame;
use Topwire\Turbo\FrameOptions;
use Topwire\Turbo\FrameRenderer;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class FrameViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function __construct(private readonly ContextStack $contextStack)
    {
    }

    public function initializeArguments(): void
    {
        $this->registerArgument('id', 'string', 'id of the frame', true);
        $this->registerArgument('src', 'string', 'Either the keyword "async", which takes current Topwire context is taken into account, when fetching the HTML asynchronously. Alternatively can be set to any URL, for full flexibility.');
        $this->registerArgument('wrapResponse', 'bool', 'Whether to wrap the response of the content in this frame. Useful, for plugins or content, that isn\'t adapted to use Hotwire frames', false, false);
        $this->registerArgument('morph', 'bool', 'Whether the response HTML should be morphed instead of fully replaced', false, false);
        $this->registerArgument('propagateUrl', 'bool', 'Whether the URL should be pushed to browser history', false, false);
        $this->registerArgument('target', 'string', 'Turbo target for links and forms within this frame');
        $this->registerArgument('additionalAttributes', 'array', 'Additional attributes for the turbo-frame tag', false, []);
    }

    public function render(): string
    {
        assert($this->renderingContext instanceof RenderingContext);
        $context = $this->contextStack->current();
        $frame = new Frame(
            baseId: $this->arguments['id'],
            wrapResponse: $this->arguments['wrapResponse'],
            scope: $context?->scope,
            renderFullDocument: $this->arguments['propagateUrl'],
        );
        if (isset($context)) {
            $context = $context->withAttribute('frame', $frame);
            $this->contextStack->push($context);
        }
        $content = $this->renderChildren();
        if (isset($context)) {
            $this->contextStack->pop();
        }
        if ($content === null) {
            return $frame->id;
        }
        $request = (new ServerRequestFromRenderingContext($this->renderingContext))->getRequest();

        return (new FrameRenderer())->render(
            frame: $frame,
            content: $content,
            options: new FrameOptions(
                src: $this->extractSourceUrl($this->arguments, $request, $context),
                target: $this->arguments['target'],
                propagateUrl: $this->arguments['propagateUrl'],
                morph: $this->arguments['morph'],
                additionalAttributes: $this->arguments['additionalAttributes'],
            ),
            context: $context,
        );
    }

    /**
     * @param array<mixed> $arguments
     */
    private function extractSourceUrl(array $arguments, ServerRequestInterface $request, ?TopwireContext $context): ?string
    {
        if (!isset($context, $arguments['src'])) {
            return null;
        }
        if ($arguments['src'] !== 'async') {
            return $arguments['src'];
        }
        $linkArguments = [];
        $pluginAttribute = $context->getAttribute('plugin');
        if ($pluginAttribute instanceof Plugin && $pluginAttribute->actionName !== null) {
            $linkArguments[$pluginAttribute->pluginNamespace] = [
                'action' => $pluginAttribute->actionName,
            ];
        }
        $site = $request->getAttribute('site');
        assert($site instanceof Site);
        return (string)$site->getRouter()->generateUri($context->contextRecord->pageId, $linkArguments);
    }
}
