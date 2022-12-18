<?php
namespace Helhum\Topwire\ContentObject;

use Helhum\Topwire\Context\TopwireContext;
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
        $context = $conf['context'];
        assert($context instanceof TopwireContext);

        $content = (new ContentObjectRenderer())->cObjGetSingle(
            'RECORDS',
            $this->transformToRecordsConfiguration($context)
        );
        if (!isset($conf['frameId'])
            || str_contains($content, sprintf('%s_%s', $conf['frameId'], $context->id))
        ) {
            // The frame id is known and set during partial rendering
            // At the same time the rendered content already contains this id, so the frame is wrapped already
            return $content;
        }

        return (new FrameRenderer())
            ->render(
                context: $context,
                content: $content,
                options: new FrameOptions(id: $conf['frameId']),
            );
    }

    /**
     * @param TopwireContext $context
     * @return array<string, mixed>
     */
    private function transformToRecordsConfiguration(TopwireContext $context): array
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
