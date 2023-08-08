<?php
declare(strict_types=1);
namespace Topwire\Typolink;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class TopwirePageLinkContext
{
    public function __construct(
        public readonly ContentObjectRenderer $contentObjectRenderer,
        public readonly TypoScriptFrontendController $frontendController,
    ) {
    }
}
