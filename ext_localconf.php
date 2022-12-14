<?php

use Helhum\TYPO3\Telegraph\ContentObject\ContentElementWrap;
use Helhum\TYPO3\Telegraph\ContentObject\TelegraphContentObject;

(static function ($extKey): void {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'][TelegraphContentObject::NAME] = TelegraphContentObject::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap']['tx_telegraph'] = ContentElementWrap::class;
})('telegraph');
