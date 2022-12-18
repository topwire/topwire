<?php
declare(strict_types=1);
namespace Helhum\Topwire\RenderingContext;

use Helhum\Topwire\RenderingContext\Exception\InvalidRenderingContext;

class RenderingContext implements \JsonSerializable
{
    public readonly string $id;

    private const hashScope = self::class;

    public function __construct(
        public readonly RenderingPath $renderingPath,
        public readonly ContextRecord $contextRecord,
    ) {
        $this->id = md5(
            $this->renderingPath->jsonSerialize()
            . $this->contextRecord->tableName
            . $this->contextRecord->id
        );
    }

    public static function fromJson(string $json): self
    {
        $objectVars = \json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $contextRecord = ContextRecord::fromJson(\json_encode($objectVars['contextRecord'], JSON_THROW_ON_ERROR));
        $renderingPath = RenderingPath::fromJson(\json_encode($objectVars['renderingPath'], JSON_THROW_ON_ERROR));
        $calculatedHash = self::calculateHmac($contextRecord, $renderingPath);
        if (!hash_equals($calculatedHash, $objectVars['hmac'] ?? '')) {
            throw new InvalidRenderingContext('Invalid topwire request', 1671023710);
        }
        return new self(
            renderingPath: $renderingPath,
            contextRecord: $contextRecord,
        );
    }

    /**
     * @return array{contextRecord: ContextRecord, renderingPath: RenderingPath, hmac: string}
     * @throws \JsonException
     */
    public function jsonSerialize(): array
    {
        return [
            'contextRecord' => $this->contextRecord,
            'renderingPath' => $this->renderingPath,
            'hmac' => self::calculateHmac($this->contextRecord, $this->renderingPath)
        ];
    }

    private static function calculateHmac(ContextRecord $contextRecord, RenderingPath $renderingPath): string
    {
        return hash_hmac(
            'sha1',
            \json_encode($contextRecord, JSON_THROW_ON_ERROR)
                . \json_encode($renderingPath, JSON_THROW_ON_ERROR),
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . self::hashScope,
        );
    }
}
