<?php
declare(strict_types=1);
namespace Topwire\Context;

class ContextStack
{
    /**
     * @var TopwireContext[]
     */
    private array $stack = [];

    public function push(TopwireContext $context): void
    {
        $this->stack[] = $context;
    }

    public function pop(): TopwireContext
    {
        if (count($this->stack) < 1) {
            // @todo use more specific exception
            throw new \LogicException('Can not pop off component info from empty stack', 1671619657);
        }
        return array_pop($this->stack);
    }

    public function current(): ?TopwireContext
    {
        if ($this->stack === []) {
            return null;
        }
        return $this->stack[array_key_last($this->stack)];
    }
}
