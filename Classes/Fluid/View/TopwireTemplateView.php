<?php

namespace Helhum\Topwire\Fluid\View;

use Helhum\Topwire\Context\ContextStack;
use Helhum\Topwire\Turbo\Frame;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\View\AbstractTemplateView;

class TopwireTemplateView extends AbstractTemplateView
{
    public function render($actionName = null)
    {
        $renderingContext = $this->getCurrentRenderingContext();
        assert($renderingContext instanceof RenderingContext);
        $this->assignContextIfAvailable($renderingContext);
        $frame = $renderingContext->getRequest()
            ->getAttribute('topwire')
            ?->getAttribute('frame');
        if (!$frame  instanceof Frame) {
            return parent::render($actionName);
        }
        [$sectionName, $partialName] = $this->partialFromFrameId(
            $frame,
            $renderingContext->getRequest()->getControllerName(),
            $actionName ?? $renderingContext->getRequest()->getControllerActionName(),
        );
        return $this->renderPartial(
            $partialName,
            $sectionName,
            (array)$renderingContext->getVariableProvider()->getAll()
        );
    }

    /**
     * @param Frame $frame
     * @param string $controllerName
     * @param string $actionName
     * @return array{0: string, 1: string}
     */
    protected function partialFromFrameId(
        Frame $frame,
        string $controllerName,
        string $actionName,
    ): array {
        return [
            $frame->partialName,
            sprintf(
                'Topwire/Frame/%s',
                $controllerName,
            )
        ];
    }

    /**
     * @todo: is this really useful? currently unused!
     *
     * @param RenderingContext $renderingContext
     */
    private function assignContextIfAvailable(RenderingContext $renderingContext): void
    {
        $context = $renderingContext->getRequest()->getAttribute('topwire');
        if ($context !== null) {
            (new ContextStack($renderingContext->getViewHelperVariableContainer()))->push($context);
        }
    }
}
