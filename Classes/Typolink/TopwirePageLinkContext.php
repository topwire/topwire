<?php
declare(strict_types=1);
namespace Topwire\Typolink;

use Psr\Http\Message\ServerRequestInterface;

class TopwirePageLinkContext
{
    public function __construct(
        public readonly ServerRequestInterface $request,
    ) {
    }
}
