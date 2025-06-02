<?php
declare(strict_types=1);
namespace Topwire\Context;

use Psr\Http\Message\ServerRequestInterface;
use Topwire\Context\Attribute\Plugin;
use Topwire\TopwireException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Service\ExtensionService;

class TopwireContextFactory
{
    public function __construct(private readonly ServerRequestInterface $request)
    {
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function forRequest(
        array $arguments,
        ?ConfigurationManagerInterface $configurationManager = null,
    ): TopwireContext {
        $extensionName = $arguments['extensionName'] ?? $this->request->getAttribute('extbase')?->getControllerExtensionName();
        $pluginName = $arguments['pluginName'] ?? $this->request->getAttribute('extbase')?->getPluginName();
        $actionName = $arguments['action'] ?? null;
        $pluginNamespace = GeneralUtility::makeInstance(ExtensionService::class)->getPluginNamespace($extensionName, $pluginName);

        // @todo: decide whether this needs to be changed, or set via argument, or maybe even removed completely
        $isOverride = isset($arguments['extensionName']);
        $contentRecordId = $isOverride ? null : $this->request->getAttribute('currentContentObject')->currentRecord ?? null;
        if ($contentRecordId !== null) {
            $contentRecordId = (string)$contentRecordId;
        }
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

    public function forPlugin(string $extensionName, string $pluginName, ?string $contextRecordId, ?int $contextPageId = null): TopwireContext
    {
        return new TopwireContext(
            $this->resolveRenderingPath($extensionName, $pluginName, null),
            $this->resolveContextRecord($contextRecordId, $contextPageId),
        );
    }

    public function forPath(string $renderingPath, ?string $contextRecordId, ?int $contextPageId = null): TopwireContext
    {
        $contextRecord = $this->resolveContextRecord($contextRecordId, $contextPageId);
        return new TopwireContext(
            new RenderingPath($renderingPath),
            $contextRecord
        );
    }

    private function resolveRenderingPath(string $extensionName, string $pluginName, ?string $pluginSignature): RenderingPath
    {
        $contentRenderingConfig = $this->request->getAttribute('frontend.typoscript')->getSetupArray()['tt_content.'] ?? [];

        $pluginSignature ??= strtolower(str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName))) . '_' . $pluginName);
        if (isset($contentRenderingConfig[$pluginSignature . '.']['20'])) {
            return new RenderingPath(sprintf('tt_content.%s.20', $pluginSignature));
        }
        // TODO: drop support for list in the future
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
        $pageUid ??= $this->request->getAttribute('routing')?->getPageId();
        if ($pageUid === null) {
            throw new TopwireException('No page uid available', 1738873179);
        }
        if ($contextRecordId === null
            || $contextRecordId === 'currentPage'
            || substr_count($contextRecordId, ':') !== 1
            || str_starts_with($contextRecordId, ':')
            || str_ends_with($contextRecordId, ':')
        ) {
            return new ContextRecord(
                'pages',
                $pageUid,
                $pageUid,
            );
        }
        [$tableName, $uid] = explode(':', $contextRecordId);
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            return new ContextRecord(
                'pages',
                $pageUid,
                $pageUid,
            );
        }
        // TODO: maybe check if the record is available
        return new ContextRecord(
            $tableName,
            (int)$uid,
            $pageUid,
        );
    }
}
