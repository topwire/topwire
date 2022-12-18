<?php
declare(strict_types=1);
namespace Helhum\Topwire\Turbo;

use Helhum\Topwire\RenderingContext\RenderingContext;

class FrameRenderer
{
    public function render(RenderingContext $renderingContext, string $content, FrameOptions $options): string
    {
        $frameId = sprintf(
            '%s_%s',
            $options->id,
            $renderingContext->id
        );
        return sprintf(
            '<turbo-frame id="%2$s"%5$s%3$s%4$s%6$s>%1$s</turbo-frame>',
            $content,
            htmlspecialchars($frameId),
            sprintf(' data-topwire-context="%s"', htmlspecialchars(\json_encode($renderingContext, JSON_THROW_ON_ERROR))),
            $options->propagateUrl ? ' data-turbo-action="advance"' : '',
            isset($options->src) ? sprintf(' src="%s"', htmlspecialchars($options->src)) : '',
            sprintf(' data-topwire-id="%s"', htmlspecialchars($options->id)),
        );
    }
}
