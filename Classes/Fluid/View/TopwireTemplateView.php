<?php

namespace Helhum\Topwire\Fluid\View;

use Helhum\Topwire\Context\Attribute\Section;
use Helhum\Topwire\Context\TopwireContext;
use Helhum\Topwire\Turbo\Frame;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Fluid\View\AbstractTemplateView;
use TYPO3Fluid\Fluid\View\Exception\InvalidSectionException;

class TopwireTemplateView extends AbstractTemplateView
{
    public function render($actionName = null)
    {
        $renderingContext = $this->getCurrentRenderingContext();
        assert($renderingContext instanceof RenderingContext);
        $context = $renderingContext->getRequest()->getAttribute('topwire');
        if (!$context instanceof TopwireContext) {
            return parent::render($actionName);
        }
        $frame = $context->getAttribute('frame');
        if ($frame  instanceof Frame) {
            $sectionName = str_replace(' ', '', ucwords(str_replace('-', ' ', strtolower($frame->baseId))));
            try {
                return $this->renderSection($sectionName, (array)$renderingContext->getVariableProvider()->getAll());
            } catch (InvalidSectionException $e) {
                // Section for frame is not found, gracefully render complete template
                return parent::render($actionName);
            }
        }
        $section = $context->getAttribute('section');
        if ($section  instanceof Section) {
            return $this->renderSection($section->sectionName, (array)$renderingContext->getVariableProvider()->getAll());
        }
        return parent::render($actionName);
    }
}
