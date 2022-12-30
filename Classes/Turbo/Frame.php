<?php
declare(strict_types=1);
namespace Helhum\Topwire\Turbo;

use Helhum\Topwire\Context\Attribute;
use Helhum\Topwire\Context\TopwireContext;
use Helhum\Topwire\Turbo\Exception\FrameIdContainsReservedToken;

class Frame implements Attribute
{
    private const idSeparatorToken = '__';
    public readonly string $id;

    public function __construct(
        public readonly string $baseId,
        public readonly bool $wrapResponse,
        ?TopwireContext $context,
    ) {
        $this->ensureValidBaseId($baseId);
        $this->id = $baseId
            . ($context === null ? '' : self::idSeparatorToken . $context->id)
        ;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @return self
     */
    public static function denormalize(array $data, array $context = []): self
    {
        return new Frame(
            $data['baseId'],
            $data['wrapResponse'],
            $context['context'],
        );
    }

    public function getCacheId(): string
    {
        return $this->wrapResponse ? $this->baseId : '';
    }

    public function jsonSerialize(): mixed
    {
        return $this->wrapResponse
            ? [
                'baseId' => $this->baseId,
                'wrapResponse' => $this->wrapResponse,
            ]
            : null;
    }

    private function ensureValidBaseId(string $id): void
    {
        if (str_contains($id, self::idSeparatorToken)) {
            throw new FrameIdContainsReservedToken(sprintf('Frame id must not contain reserved token "%s"', self::idSeparatorToken));
        }
    }
}
