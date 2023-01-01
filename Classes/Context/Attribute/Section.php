<?php

declare(strict_types=1);

namespace Helhum\Topwire\Context\Attribute;

use Helhum\Topwire\Context\Attribute;

class Section implements Attribute
{
    public function __construct(
        public readonly string $sectionName,
    ) {
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
