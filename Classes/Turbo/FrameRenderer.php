<?php
declare(strict_types=1);
namespace Helhum\Topwire\Turbo;

use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

class FrameRenderer
{
    public function render(Frame $frame, string $content, FrameOptions $options): string
    {
        $tagBuilder = new TagBuilder('turbo-frame', $content);
        $tagBuilder->addAttribute('id', $frame->id);
        $tagBuilder->addAttribute('data-topwire-context', $frame->toHashedString());
        if ($options->propagateUrl) {
            $tagBuilder->addAttribute('data-turbo-action', 'advance');
        }
        if (isset($options->src)) {
            $tagBuilder->addAttribute('src', $options->src);
        }

        return $tagBuilder->render();
    }
}
