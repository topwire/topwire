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
    public readonly string $partialName;

    public function __construct(
        public readonly string $baseId,
        public readonly bool $wrapResponse,
        public readonly ?string $scope,
    ) {
        $this->ensureValidBaseId($baseId);
        $this->partialName = str_replace(' ', '', ucwords(str_replace('-', ' ', strtolower($baseId))));
        $this->id = $baseId
            . ($scope === null ? '' : self::idSeparatorToken . $scope)
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
            $data['wrapResponse'] ?? false,
            $context['context']->scope,
        );
    }

    public function getCacheId(): string
    {
        return $this->wrapResponse ? $this->baseId : '';
    }

    public function jsonSerialize(): mixed
    {
        $data = [
            'baseId' => $this->baseId,
        ];
        if ($this->wrapResponse) {
            $data['wrapResponse'] = $this->wrapResponse;
        }
        return $data;
    }

    private function ensureValidBaseId(string $id): void
    {
        if (str_contains($id, self::idSeparatorToken)) {
            throw new FrameIdContainsReservedToken(sprintf('Frame id must not contain reserved token "%s"', self::idSeparatorToken));
        }
    }
}
