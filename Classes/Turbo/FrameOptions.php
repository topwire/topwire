<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Telegraph\Turbo;

class FrameOptions
{
    public function __construct(
        public readonly string $id,
        public readonly bool $propagateUrl = false,
    ) {
    }
}
