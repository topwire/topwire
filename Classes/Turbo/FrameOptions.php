<?php
declare(strict_types=1);
namespace Topwire\Turbo;

class FrameOptions
{
    /**
     * @param array<string, string> $additionalAttributes
     */
    public function __construct(
        public readonly bool $wrapResponse = false,
        public readonly ?string $src = null,
        public readonly ?string $target = null,
        public readonly bool $propagateUrl = false,
        public readonly bool $morph = false,
        public readonly array $additionalAttributes = [],
    ) {
    }
}
