<?php

use Topwire\ContentObject\ContentElementWrap;
use Topwire\Typolink\TopwirePageLinkBuilder;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Frontend\Typolink\PageLinkBuilder;

(static function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = 'tx_topwire';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap']['tx_topwire'] = ContentElementWrap::class;
    if ($GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['page'] !== PageLinkBuilder::class) {
        $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['overriddenDefault'] = $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['page'];
    }
    $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['page'] = TopwirePageLinkBuilder::class;

    ExtensionManagementUtility::addTypoScript(
        'topwire',
        'setup',
        "
        [(traverse(((request ?? null)?.getHeaders() ?? []), 'topwire-context') == true && ((request ?? null)?.getHeaders() ?? [])['topwire-context'] !== '') || (traverse(((request ?? null)?.getQueryParams() ?? []), 'tx_topwire') && ((request ?? null)?.getQueryParams() ?? [])['tx_topwire'] !== '')]
            # fake condition to influence the page cache
            # the TopwireRenderContentElementByContext event listener does
            # work as expected
            # But this does not solve the problem that the typoscript cache is
            # build based on this condition as we've modified the setup with
            # dirty hack in the TopwireRenderContentElementByContext event listener
            # To work around this the only way is to set the topwire content object manually
            # by something like this:
            #
            #
            # config.debug = 0
            # config.disableAllHeaderCode = 1
            # config.disableCharsetHeader = 1
            # page >
            # page = PAGE
            # page.10 = TOPWIRE
            #
            # This is not a general solution as it specific to the installation
        [global]
        ",
    );
})();
