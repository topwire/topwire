<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Telegraph\RenderingContext;

use Helhum\TYPO3\Telegraph\RenderingContext\Exception\InvalidRenderingContext;
use Helhum\TYPO3\Telegraph\RenderingContext\Exception\TableNameNotFound;

class ContextRecord implements \JsonSerializable
{
    public function __construct(
        public readonly string $tableName,
        public readonly int $id,
        public readonly int $pageId
    ) {
        if (!isset($GLOBALS['TCA'][$tableName])) {
            throw new TableNameNotFound(sprintf('Table name "%s" is invalid', 1671023687));
        }
    }

    public static function fromJson(string $json): self
    {
        $objectVars = \json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!isset($objectVars['tableName'], $objectVars['id'], $objectVars['pageId'])) {
            throw new InvalidRenderingContext('Could not decode context record', 1671024039);
        }
        return new self(tableName: $objectVars['tableName'], id: $objectVars['id'], pageId: $objectVars['pageId']);
    }

    /**
     * @return array{tableName: string, id: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'tableName' => $this->tableName,
            'id' => $this->id,
            'pageId' => $this->pageId,
        ];
    }
}
