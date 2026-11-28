<?php
declare(strict_types=1);
namespace Topwire\EventListener;

use Psr\Http\Message\ServerRequestInterface;
use Topwire\Context\ContextRecord;
use Topwire\Context\TopwireContext;
use Topwire\Context\TopwireContextFactory;
use Topwire\Exception\InvalidConfiguration;
use Topwire\Turbo\Frame;
use Topwire\Typolink\TopwirePageLinkContext;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Event\ModifyPageLinkConfigurationEvent;

class TopwirePageLinkModifier
{
    private const virtualLinkNamespace = 'topwire';
    private const configNamespace = self::virtualLinkNamespace . '.';
    private const linkNamespace = 'tx_topwire';

    public function modifyQueryParameters(ModifyPageLinkConfigurationEvent $linkConfigurationEvent): void
    {
        $linkDetails = $linkConfigurationEvent->getLinkDetails();
        $linkConfiguration = $linkConfigurationEvent->getConfiguration();
        $queryParameters = $linkConfigurationEvent->getQueryParameters();
        $pageLinkContext = $this->extractTopwireLinkContext($linkConfigurationEvent);
        if ($pageLinkContext === null) {
            return;
        }
        $queryParameters = $this->buildQueryParameters(
            $pageLinkContext,
            $linkConfiguration,
            $queryParameters,
            $linkDetails['pageuid'] ?? null
        );
        $linkConfigurationEvent->setQueryParameters($queryParameters);
    }

    private function extractTopwireLinkContext(ModifyPageLinkConfigurationEvent $linkConfigurationEvent): ?TopwirePageLinkContext
    {
        if (!isset($linkConfigurationEvent->getQueryParameters()[self::virtualLinkNamespace])
            && !isset($linkConfigurationEvent->getConfiguration()[self::configNamespace])
        ) {
            return null;
        }
        $request = $linkConfigurationEvent->getRequest();
        $contentObject = $request->getAttribute('currentContentObject');
        if ($contentObject instanceof ContentObjectRenderer) {
            return new TopwirePageLinkContext($contentObject, $request);
        }
        return null;
    }

    /**
     * @param array<mixed> $linkConfiguration
     * @param array<mixed> $queryParameters
     * @return array<mixed>
     * @throws InvalidConfiguration
     * @throws \JsonException
     */
    private function buildQueryParameters(TopwirePageLinkContext $pageLinkContext, array $linkConfiguration, array $queryParameters, ?int $contextPageId = null): array
    {
        $context = $this->resolveContext($pageLinkContext, $linkConfiguration, $queryParameters, $contextPageId);
        unset($queryParameters[self::virtualLinkNamespace]);
        $queryParameters[self::linkNamespace] = $context->toHashedString();
        return $queryParameters;
    }

    /**
     * @param array<mixed> $linkConfiguration
     * @param array<mixed> $queryParameters
     * @throws InvalidConfiguration
     */
    private function resolveContext(TopwirePageLinkContext $pageLinkContext, array $linkConfiguration, array $queryParameters, ?int $contextPageId = null): TopwireContext
    {
        $topwireArguments = $this->resolveTopwireArguments($linkConfiguration, $queryParameters);
        assert(in_array($topwireArguments['type'], ['plugin', 'contentElement', 'typoScript', 'context'], true));
        $contextRecordId = $pageLinkContext->contentObjectRenderer->currentRecord;
        if (isset($topwireArguments['tableName'], $topwireArguments['recordUid'])) {
            $contextRecordId = $topwireArguments['tableName'] . ':' . $topwireArguments['recordUid'];
        }
        $contextFactory = new TopwireContextFactory($pageLinkContext->request);
        $context = match ($topwireArguments['type']) {
            'context' => $this->resolveFromRequest($pageLinkContext->request, $contextPageId),
            'plugin' => $contextFactory->forPlugin($topwireArguments['extensionName'], $topwireArguments['pluginName'], $contextRecordId, $contextPageId),
            'contentElement' => $contextFactory->forPath('tt_content', 'tt_content:' . $topwireArguments['uid']),
            'typoScript' => $contextFactory->forPath($topwireArguments['typoScriptPath'], $contextRecordId, $contextPageId),
        };
        $frame = new Frame(
            baseId: $topwireArguments['frameId'] ?? 'link',
            wrapResponse: isset($topwireArguments['wrapResponse']) && (bool)(int)$topwireArguments['wrapResponse'],
            scope: $context->scope,
        );
        return $context->withAttribute('frame', $frame);
    }

    /**
     * @throws InvalidConfiguration
     */
    private function resolveFromRequest(ServerRequestInterface $request, ?int $contextPageId): TopwireContext
    {
        $context = $request->getAttribute('topwire');
        if (!$context instanceof TopwireContext) {
            throw new InvalidConfiguration('Topwire type was set to "context", but no context could be resolved', 1676815069);
        }
        if ($contextPageId === null) {
            return $context;
        }
        return $context->withContextRecord(new ContextRecord($context->contextRecord->tableName, $context->contextRecord->id, $contextPageId));
    }

    /**
     * @param array<mixed> $linkConfiguration
     * @param array<mixed> $queryParameters
     * @return array<mixed>
     * @throws \LogicException
     * @throws InvalidConfiguration
     */
    private function resolveTopwireArguments(array $linkConfiguration, array $queryParameters): array
    {
        if (isset($linkConfiguration[self::configNamespace])) {
            return $this->ensureValidArguments($linkConfiguration[self::configNamespace]);
        }
        if (isset($queryParameters[self::virtualLinkNamespace])) {
            return $this->ensureValidArguments($queryParameters[self::virtualLinkNamespace]);
        }
        throw new \LogicException('Topwire link requested, but to link configuration is present', 1691582649);
    }

    /**
     * @param array<mixed> $topwireArguments
     * @return array<mixed>
     * @throws InvalidConfiguration
     */
    private function ensureValidArguments(array $topwireArguments): array
    {
        try {
            match ($topwireArguments['type'] ?? null) {
                'context' => true,
                'plugin' => !isset($topwireArguments['extensionName'], $topwireArguments['pluginName'])
                    && throw new InvalidConfiguration('URLs of type "plugin" must have "extensionName" and "pluginName" set', 1671558884),
                'contentElement' => !isset($topwireArguments['uid'])
                    && throw new InvalidConfiguration('URLs of type "contentElement" must have "uid" set', 1671560042),
                'typoScript' => !isset($topwireArguments['typoScriptPath'])
                    && throw new InvalidConfiguration('URLs of type "typoScript" must have "typoScriptPath" set', 1671558886),
            };
        } catch (\UnhandledMatchError $e) {
            throw new InvalidConfiguration('URL type must be set and must be either "plugin", "contentElement" or "typoScript"', 1671560111, $e);
        }
        return $topwireArguments;
    }
}
