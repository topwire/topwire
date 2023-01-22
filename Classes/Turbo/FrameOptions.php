<?php
declare(strict_types=1);
namespace Helhum\Topwire\Turbo;

class FrameOptions
{
    public function __construct(
        public readonly ?string $src = null,
        public readonly bool $propagateUrl = false,
        public readonly bool $wrapResponse = false,
        public readonly bool $morph = false,
    ) {
    }
}
