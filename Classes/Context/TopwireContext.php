<?php
declare(strict_types=1);
namespace Helhum\Topwire\Context;

use Psr\Http\Message\ServerRequestInterface;

class TopwireContext implements \JsonSerializable
{
    public const headerName = 'Topwire-Context';
    public const argumentName = 'tx_topwire';

    public readonly string $scope;
    public readonly string $cacheId;

    /**
     * @var array<string, Attribute>
     */
    private array $attributes = [];

    public function __construct(
        public readonly RenderingPath $renderingPath,
        public readonly ContextRecord $contextRecord,
        ?string $cacheId = null,
    ) {
        $this->scope = md5(
            $this->renderingPath->jsonSerialize()
            . $this->contextRecord->tableName
            . $this->contextRecord->id
        );
        $this->cacheId = $cacheId ?? ($this->scope . $this->contextRecord->pageId);
    }

    public static function fromUntrustedString(string $untrustedString, ContextDenormalizer $denormalizer): self
    {
        $data = \json_decode(
            TopwireHash::fromUntrustedString($untrustedString)->secureString,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        return $denormalizer->denormalize($data);
    }

    public static function isRequestSubmitted(?ServerRequestInterface $request): bool
    {
        return isset($request?->getQueryParams()[self::argumentName]) || ($request?->hasHeader(self::headerName) ?? false);
    }

    public function toHashedString(): string
    {
        return (new TopwireHash(\json_encode($this, JSON_THROW_ON_ERROR)))
            ->hashedString;
    }

    public function withAttribute(
        string $name,
        Attribute $attribute,
    ): self {
        $newContext = new self(
            $this->renderingPath,
            $this->contextRecord,
            $this->cacheId . $attribute->getCacheId()
        );
        $newContext->attributes = $this->attributes;
        $newContext->attributes[$name] = $attribute;
        return $newContext;
    }

    public function getAttribute(string $name): ?Attribute
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * @return array{renderingPath: RenderingPath, contextRecord: ContextRecord, attributes?: array<string, Attribute>}
     */
    public function jsonSerialize(): array
    {
        $normalizedContext = [
            'renderingPath' => $this->renderingPath,
            'contextRecord' => $this->contextRecord,
        ];
        $attributes = array_filter(
            $this->attributes,
            static fn (Attribute $attribute) => $attribute->jsonSerialize() !== null,
        );
        if ($attributes !== []) {
            $normalizedContext['attributes'] = $attributes;
        }
        return $normalizedContext;
    }
}
