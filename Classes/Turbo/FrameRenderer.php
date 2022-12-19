<?php
declare(strict_types=1);
namespace Helhum\Topwire\Turbo;

use Helhum\Topwire\Context\TopwireContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

class FrameRenderer
{
    public function render(TopwireContext $context, string $content, FrameOptions $options): string
    {
        $frameId = new FrameId(
            $options->id,
            \json_encode($context, JSON_THROW_ON_ERROR)
        );
        $tagBuilder = new TagBuilder('turbo-frame', $content);
        $tagBuilder->addAttribute('id', $frameId->id);
        if ($options->propagateUrl) {
            $tagBuilder->addAttribute('data-turbo-action', 'advance');
        }
        if (isset($options->src)) {
            $tagBuilder->addAttribute('src', $options->src);
        }

        return $tagBuilder->render();
    }
}
