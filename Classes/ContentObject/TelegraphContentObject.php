<?php
namespace Helhum\TYPO3\Telegraph\ContentObject;

use Helhum\TYPO3\Telegraph\RenderingContext\RenderingContext;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class TelegraphContentObject extends AbstractContentObject
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

        return (new ContentObjectRenderer())->cObjGetSingle(
            'RECORDS',
            $this->transformToRecordsConfiguration($renderingContext)
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
