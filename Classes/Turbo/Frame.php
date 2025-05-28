<?php
declare(strict_types=1);
namespace Topwire\Turbo;

use Topwire\Context\Attribute;

class Frame implements Attribute
{
    private const idSeparatorToken = '__';
    public readonly string $id;

    public function __construct(
        public readonly string $baseId,
        public readonly bool $wrapResponse,
        public readonly ?string $scope,
        public readonly bool $renderFullDocument = false,
    ) {
        $this->id = $baseId
            . ($scope === null ? '' : self::idSeparatorToken . $scope)
        ;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     */
    public static function denormalize(array $data, array $context = []): self
    {
        return new Frame(
            baseId: $data['baseId'],
            wrapResponse: $data['wrapResponse'] ?? false,
            scope: array_key_exists('scope', $data) ? $data['scope'] : $context['context']?->scope,
            renderFullDocument: $data['renderFullDocument'] ?? false,
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
            $data['scope'] = $this->scope;
        }
        if ($this->renderFullDocument) {
            $data['renderFullDocument'] = $this->renderFullDocument;
        }
        return $data;
    }
}
