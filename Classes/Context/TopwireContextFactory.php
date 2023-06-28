<?php
declare(strict_types=1);
namespace Topwire\Context;

use Topwire\Context\Attribute\Plugin;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class TopwireContextFactory
{
    private TypoScriptFrontendController $typoScriptFrontendController;

    public function __construct(TypoScriptFrontendController $typoScriptFrontendController)
    {
        $this->typoScriptFrontendController = $typoScriptFrontendController;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function forRequest(
        ServerRequestInterface $request,
        array $arguments,
        ?ConfigurationManagerInterface $configurationManager = null,
    ): TopwireContext {
        $extensionName = $arguments['extensionName'] ?? $request->getAttribute('extbase')?->getControllerExtensionName();
        $pluginName = $arguments['pluginName'] ?? $request->getAttribute('extbase')?->getPluginName();
        $actionName = $arguments['action'] ?? null;
        $configurationManager ??= GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $extensionService = new ExtensionService();
        $extensionService->injectConfigurationManager($configurationManager);
        $pluginNamespace = $extensionService->getPluginNamespace($extensionName, $pluginName);

        // @todo: decide whether this needs to be changed, or set via argument, or maybe even removed completely
        $isOverride = isset($arguments['extensionName']);
        $contentRecordId = $isOverride ? null : $configurationManager->getContentObject()?->currentRecord;

        $plugin = new Plugin(
            extensionName: $extensionName,
            pluginName: $pluginName,
            pluginNamespace: $pluginNamespace,
            actionName: $actionName,
            isOverride: $isOverride,
            forRecord: $contentRecordId,
            forPage: $arguments['pageUid'] ?? null,
        );
        return (new TopwireContext(
            $this->resolveRenderingPath($plugin->extensionName, $plugin->pluginName, $plugin->pluginSignature),
            $this->resolveContextRecord($plugin->forRecord),
        ))->withAttribute('plugin', $plugin);
    }

    public function forPlugin(string $extensionName, string $pluginName, ?string $contextRecordId): TopwireContext
    {
        return new TopwireContext(
            $this->resolveRenderingPath($extensionName, $pluginName, null),
            $this->resolveContextRecord($contextRecordId),
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

    private function resolveRenderingPath(string $extensionName, string $pluginName, ?string $pluginSignature): RenderingPath
    {
        $contentRenderingConfig = $this->typoScriptFrontendController->tmpl->setup['tt_content.'];
        $pluginSignature ??= strtolower(str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName))) . '_' . $pluginName);
        if (isset($contentRenderingConfig[$pluginSignature . '.']['20'])) {
            return new RenderingPath(sprintf('tt_content.%s.20', $pluginSignature));
        }
        if (isset($contentRenderingConfig['list.']['20.'][$pluginSignature])) {
            return new RenderingPath(sprintf('tt_content.list.20.%s', $pluginSignature));
        }
        return new RenderingPath('tt_content');
    }

    /**
     * Resolves the table name and uid for the record the rendering is based upon.
     * Falls back to current page if none is available
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
