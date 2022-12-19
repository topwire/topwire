<?php
declare(strict_types=1);
namespace Helhum\Topwire\Context;

use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Request as ExtbaseRequest;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class TopwireContextFactory
{
    private TypoScriptFrontendController $typoScriptFrontendController;

    public function __construct(TypoScriptFrontendController $typoScriptFrontendController)
    {
        $this->typoScriptFrontendController = $typoScriptFrontendController;
    }

    public function forExtbaseRequest(ExtbaseRequest $request, ConfigurationManagerInterface $configurationManager): TopwireContext
    {
        return $this->forPlugin(
            $request->getControllerExtensionName(),
            $request->getPluginName(),
            $configurationManager->getContentObject()?->currentRecord,
        );
    }

    public function forPlugin(string $extensionName, string $pluginName, ?string $contextRecordId, ?int $pageUid = null): TopwireContext
    {
        return new TopwireContext(
            RenderingPath::fromPlugin($extensionName, $pluginName, $this->typoScriptFrontendController->tmpl->setup['tt_content.']),
            $this->resolveContextRecord($contextRecordId, $pageUid),
        );
    }

    public function forPath(string $renderingPath, ?string $contextRecordId): TopwireContext
    {
        $contextRecord = $this->resolveContextRecord($contextRecordId);
        return new TopwireContext(
            new RenderingPath($renderingPath),
            $contextRecord
        );
    }

    /**
     * Resolves the table name and uid for the record the rendering is based upon.
     * Falls back to current page if none is available
     *
     * @param string|null $contextRecordId
     * @param int|null $pageUid
     * @return ContextRecord
     */
    private function resolveContextRecord(?string $contextRecordId, ?int $pageUid = null): ContextRecord
    {
        if ($contextRecordId === null
            || $contextRecordId === 'currentPage'
            || substr_count($contextRecordId, ':') !== 1
        ) {
            return new ContextRecord(
                'pages',
                (int)$this->typoScriptFrontendController->id,
                $pageUid ?? (int)$this->typoScriptFrontendController->id,
            );
        }
        [$tableName, $uid] = explode(':', $contextRecordId);
        if (empty($tableName) || empty($uid) || !MathUtility::canBeInterpretedAsInteger($uid)) {
            return new ContextRecord(
                'pages',
                (int)$this->typoScriptFrontendController->id,
                $pageUid ?? (int)$this->typoScriptFrontendController->id,
            );
        }
        // TODO: maybe check if the record is available
        return new ContextRecord(
            $tableName,
            (int)$uid,
            $pageUid ?? (int)$this->typoScriptFrontendController->id,
        );
    }
}