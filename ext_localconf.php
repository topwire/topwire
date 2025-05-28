<?php

use Topwire\ContentObject\ContentElementWrap;
use Topwire\ContentObject\TopwireContentObject;
use Topwire\ContentObject\TopwireUserContentObject;
use Topwire\Typolink\TopwirePageLinkBuilder;
use Topwire\Typolink\TopwirePageLinkModifier;
use TYPO3\CMS\Frontend\Typolink\PageLinkBuilder;

(static function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_topwire';
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_topwire_document';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap']['tx_topwire'] = ContentElementWrap::class;
    if ($GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['page'] !== PageLinkBuilder::class) {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['overriddenDefault'] = $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['page'];
    }
    $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['page'] = TopwirePageLinkBuilder::class;
    // @deprecated can be removed when TYPO3 11 compat is removed
    $GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'][TopwireUserContentObject::NAME] = TopwireUserContentObject::class;
    $GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'][TopwireContentObject::NAME] = TopwireContentObject::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typolinkProcessing']['typolinkModifyParameterForPageLinks'][TopwirePageLinkModifier::class] = TopwirePageLinkModifier::class;
})();
