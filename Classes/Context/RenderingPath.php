<?php
declare(strict_types=1);
namespace Helhum\Topwire\Context;

class RenderingPath implements \JsonSerializable
{
    public function __construct(private readonly string $renderingPath)
    {
    }

    public function jsonSerialize(): string
    {
        return $this->renderingPath;
    }
}
