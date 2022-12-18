<?php
declare(strict_types=1);
namespace Helhum\Topwire\Turbo;

use Helhum\Topwire\Context\TopwireContext;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

class FrameRenderer
{
    public function render(TopwireContext $context, string $content, FrameOptions $options): string
    {
        $frameId = sprintf(
            '%s_%s',
            $options->id,
            $context->id
        );
        $tagBuilder = new TagBuilder('turbo-frame', $content);
        $tagBuilder->addAttribute('id', $frameId);
        $tagBuilder->addAttribute('data-topwire-id', $options->id);
        $tagBuilder->addAttribute('data-topwire-context', \json_encode($context, JSON_THROW_ON_ERROR));
        if ($options->propagateUrl) {
            $tagBuilder->addAttribute('data-turbo-action', 'advance');
        }
        if (isset($options->src)) {
            $tagBuilder->addAttribute('src', $options->src);
        }

        return $tagBuilder->render();
    }
}
