<?php
namespace Helhum\Topwire\ContentObject;

use Helhum\Topwire\RenderingContext\RenderingContext;
use Helhum\Topwire\Turbo\FrameOptions;
use Helhum\Topwire\Turbo\FrameRenderer;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class TopwireContentObject extends AbstractContentObject
{
    public const NAME = 'TELEGRAPH';

    /**
     * @param array<mixed> $conf
     * @return string
     */
    public function render($conf = []): string
    {
        $renderingContext = $conf['context'];
        assert($renderingContext instanceof RenderingContext);

        $content = (new ContentObjectRenderer())->cObjGetSingle(
            'RECORDS',
            $this->transformToRecordsConfiguration($renderingContext)
        );
        if (!isset($conf['frameId'])
            || str_contains($content, sprintf('%s_%s', $conf['frameId'], $renderingContext->id))
        ) {
            // The frame id is known and set during partial rendering
            // At the same time the rendered content already contains this id, so the frame is wrapped already
            return $content;
        }

        return (new FrameRenderer())
            ->render(
                renderingContext: $renderingContext,
                content: $content,
                options: new FrameOptions(id: $conf['frameId']),
            );
    }

    /**
     * @param RenderingContext $context
     * @return array<string, mixed>
     */
    private function transformToRecordsConfiguration(RenderingContext $context): array
    {
        return [
            'source' => $context->contextRecord->tableName . '_' . $context->contextRecord->id,
            'tables' => $context->contextRecord->tableName,
            'conf.' => [
                $context->contextRecord->tableName => '< ' . $context->renderingPath->jsonSerialize(),
            ],
        ];
    }
}
