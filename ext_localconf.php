<?php

use Helhum\Topwire\ContentObject\ContentElementWrap;
use Helhum\Topwire\ContentObject\TopwireContentObject;

(static function ($extKey): void {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'][TopwireContentObject::NAME] = TopwireContentObject::class;
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_topwire';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap']['tx_topwire'] = ContentElementWrap::class;
})('topwire');
