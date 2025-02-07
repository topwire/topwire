<?php
declare(strict_types=1);
namespace Topwire\Typolink;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class TopwirePageLinkContext
{
    public function __construct(
        public readonly ServerRequestInterface $request,
    ) {
    }
}
