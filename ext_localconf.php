<?php

use Helhum\Topwire\ContentObject\ContentElementWrap;
use Helhum\Topwire\ContentObject\TopwireContentObject;
use Helhum\Topwire\Typolink\TopwirePageLinkBuilder;
use TYPO3\CMS\Frontend\Typolink\PageLinkBuilder;

(static function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'][TopwireContentObject::NAME] = TopwireContentObject::class;
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_topwire';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap']['tx_topwire'] = ContentElementWrap::class;
    if ($GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['page'] !== PageLinkBuilder::class) {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['overriddenDefault'] = $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['page'];
    }
    $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['page'] = TopwirePageLinkBuilder::class;
})();
