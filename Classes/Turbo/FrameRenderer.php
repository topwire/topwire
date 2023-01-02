<?php
declare(strict_types=1);
namespace Helhum\Topwire\Turbo;

use Helhum\Topwire\Context\TopwireContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

class FrameRenderer
{
    public function render(Frame $frame, string $content, ?FrameOptions $options = null, ?TopwireContext $context = null): string
    {
        $tagBuilder = new TagBuilder('turbo-frame', $content);
        $tagBuilder->addAttribute('id', $frame->id);
        if ($context instanceof TopwireContext) {
            $tagBuilder->addAttribute('data-topwire-context', $context->toHashedString());
        }
        if ($options?->propagateUrl === true) {
            $tagBuilder->addAttribute('data-turbo-action', 'advance');
        }
        if (!empty($options?->src)) {
            $tagBuilder->addAttribute('src', $options->src);
        }

        return $tagBuilder->render();
    }
}
