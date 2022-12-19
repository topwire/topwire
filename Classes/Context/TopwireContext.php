<?php
declare(strict_types=1);
namespace Helhum\Topwire\Context;

class TopwireContext implements \JsonSerializable
{
    public readonly string $id;
    public readonly string $cacheId;

    public function __construct(
        public readonly RenderingPath $renderingPath,
        public readonly ContextRecord $contextRecord,
    ) {
        $this->id = md5(
            $this->renderingPath->jsonSerialize()
            . $this->contextRecord->tableName
            . $this->contextRecord->id
        );
        $this->cacheId = $this->id . $this->contextRecord->pageId;
    }

    /**
     * @param array{renderingPath: string, contextRecord: array{tableName: string, id: int, pageId: int}} $objectVars
     * @return self
     */
    public static function fromArray(array $objectVars): self
    {
        return new self(
            renderingPath: new RenderingPath($objectVars['renderingPath']),
            contextRecord: new ContextRecord(...$objectVars['contextRecord']),
        );
    }

    public static function fromUntrustedString(string $untrustedString): self
    {
        $objectVars = \json_decode(
            TopwireHash::fromUntrustedString($untrustedString)->secureString,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        return self::fromArray($objectVars['context']);
    }

    /**
     * @return array{renderingPath: RenderingPath, contextRecord: ContextRecord}
     */
    public function jsonSerialize(): array
    {
        return [
            'renderingPath' => $this->renderingPath,
            'contextRecord' => $this->contextRecord,
        ];
    }
}
