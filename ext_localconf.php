<?php

use Topwire\Typolink\TopwirePageLinkBuilder;
use TYPO3\CMS\Frontend\Typolink\PageLinkBuilder;

(static function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_topwire';
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_topwire_document';
    if ($GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['page'] !== PageLinkBuilder::class) {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['overriddenDefault'] = $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['page'];
    }
    $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['page'] = TopwirePageLinkBuilder::class;
})();
