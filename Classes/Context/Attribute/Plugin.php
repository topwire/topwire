<?php

declare(strict_types=1);

namespace Topwire\Context\Attribute;

use Topwire\Context\Attribute;

class Plugin implements Attribute
{
    public readonly string $pluginSignature;

    public function __construct(
        public readonly string $extensionName,
        public readonly string $pluginName,
        public readonly string $pluginNamespace,
        public readonly ?string $actionName = null,
        public readonly bool $isOverride = false,
        public readonly ?string $forRecord = null,
        public readonly ?int $forPage = null,
    ) {
        $this->pluginSignature = strtolower($extensionName . '_' . $pluginName);
    }

    public function getCacheId(): string
    {
        return '';
    }

    public static function denormalize(array $data, array $context = []): ?Attribute
    {
        return null;
    }

    public function jsonSerialize(): mixed
    {
        return null;
    }
}
