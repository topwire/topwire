<?php

declare(strict_types=1);

namespace Helhum\Topwire\Context;

interface Attribute extends \JsonSerializable
{
    public function getCacheId(): string;

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     */
    public static function denormalize(array $data, array $context = []): ?Attribute;
}
