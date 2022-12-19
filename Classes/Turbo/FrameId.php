<?php
declare(strict_types=1);
namespace Helhum\Topwire\Turbo;

use Helhum\Topwire\Turbo\Exception\FrameIdContainsReservedToken;

class FrameId
{
    private const idSeparatorToken = '::';

    public readonly string $id;

    public function __construct(
        public readonly string $baseId,
        public readonly string $context,
    ) {
        $this->ensureValidBaseId($baseId);
        $this->id = sprintf(
            '%s::%s',
            $baseId,
            $context
        );
    }

    public static function fromHeaderString(string $headerString): self
    {
        return new self(
            ...explode(self::idSeparatorToken, $headerString)
        );
    }

    private function ensureValidBaseId(string $id): void
    {
        if (str_contains($id, self::idSeparatorToken)) {
            throw new FrameIdContainsReservedToken(sprintf('Frame id must not contain reserved token "%s"', self::idSeparatorToken));
        }
    }
}
