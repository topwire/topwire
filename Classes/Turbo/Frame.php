<?php
declare(strict_types=1);
namespace Helhum\Topwire\Turbo;

use Helhum\Topwire\Context\TopwireContext;
use Helhum\Topwire\Context\TopwireHash;
use Helhum\Topwire\Turbo\Exception\FrameIdContainsReservedToken;

class Frame implements \JsonSerializable
{
    private const idSeparatorToken = '__';

    public readonly string $id;
    public readonly string $cacheId;

    public function __construct(
        public readonly string $baseId,
        public readonly TopwireContext $context,
        public readonly bool $wrapResponse = false,
    ) {
        $this->ensureValidBaseId($baseId);
        $this->id = $baseId
            . self::idSeparatorToken
            . \json_encode($this)
        ;
        $this->cacheId = $this->context->cacheId . ($this->wrapResponse ? $baseId : '');
    }

    public static function fromUntrustedString(string $untrustedString): self
    {
        [$baseIdString, $untrustedFrameString] = explode(self::idSeparatorToken, $untrustedString);
        $objectVars = \json_decode(
            TopwireHash::fromUntrustedString($untrustedFrameString)->secureString,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        return new self(
            baseId: $objectVars['baseId'] ?? $baseIdString,
            context: TopwireContext::fromArray($objectVars['context']),
            wrapResponse: $objectVars['wrapResponse'] ?? false,
        );
    }

    public function toHashedString(): string
    {
        return $this->baseId
            . self::idSeparatorToken
            . (new TopwireHash(\json_encode($this, JSON_THROW_ON_ERROR)))->hashedString
        ;
    }

    /**
     * @return array{baseId?: string, context: TopwireContext, wrapResponse?: bool}
     */
    public function jsonSerialize(): array
    {
        $objectVars = [
            'baseId' => $this->baseId,
            'context' => $this->context,
            'wrapResponse' => $this->wrapResponse,
        ];
        if (!$this->wrapResponse) {
            unset($objectVars['baseId'], $objectVars['wrapResponse']);
        }
        return $objectVars;
    }

    private function ensureValidBaseId(string $id): void
    {
        if (str_contains($id, self::idSeparatorToken)) {
            throw new FrameIdContainsReservedToken(sprintf('Frame id must not contain reserved token "%s"', self::idSeparatorToken));
        }
    }
}
