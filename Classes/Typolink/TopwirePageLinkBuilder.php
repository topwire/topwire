<?php
declare(strict_types=1);
namespace Topwire\Typolink;

use Topwire\Context\TopwireContext;
use Topwire\Context\TopwireContextFactory;
use Topwire\Exception\InvalidConfiguration;
use Topwire\Turbo\Frame;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\CMS\Frontend\Typolink\PageLinkBuilder;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;

class TopwirePageLinkBuilder extends PageLinkBuilder
{
    private const virtualLinkNamespace = 'topwire';
    private const linkNamespace = 'tx_topwire';

    private readonly ?AbstractTypolinkBuilder $originalPageLinkBuilder;

    public function __construct(
        ContentObjectRenderer $contentObjectRenderer,
        TypoScriptFrontendController $typoScriptFrontendController = null
    ) {
        $defaultLinkBuilderClass = $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['overriddenDefault'] ?? null;
        if (is_string($defaultLinkBuilderClass)
            && is_subclass_of($defaultLinkBuilderClass, AbstractTypolinkBuilder::class)
        ) {
            $this->originalPageLinkBuilder = GeneralUtility::makeInstance(
                $defaultLinkBuilderClass,
                $contentObjectRenderer,
                $typoScriptFrontendController
            );
        }
        parent::__construct($contentObjectRenderer, $typoScriptFrontendController);
    }

    /**
     * @param array<mixed> $linkDetails
     * @param array<mixed> $conf
     * @throws UnableToLinkException
     */
    public function build(array &$linkDetails, string $linkText, string $target, array $conf): LinkResultInterface
    {
        $conf = $this->amendUrlParams($conf);
        if (isset($this->originalPageLinkBuilder)) {
            $result = $this->originalPageLinkBuilder->build($linkDetails, $linkText, $target, $conf);
            assert($result instanceof LinkResultInterface);
            return $result;
        }
        return parent::build($linkDetails, $linkText, $target, $conf);
    }

    /**
     * @param array<mixed> $conf
     * @return array<mixed>
     * @throws \JsonException
     */
    private function amendUrlParams(array $conf): array
    {
        if ((!isset($conf['additionalParams']) || !str_contains($conf['additionalParams'], self::virtualLinkNamespace))
            && !isset($conf['topwire.'])
        ) {
            return $conf;
        }
        $context = $this->resolveContext($conf);
        if (!$context instanceof TopwireContext) {
            return $conf;
        }
        parse_str($conf['additionalParams'] ?? '', $queryArguments);
        unset($queryArguments[self::virtualLinkNamespace]);
        $queryArguments[self::linkNamespace] = $context->toHashedString();
        $conf['additionalParams'] = '&' . HttpUtility::buildQueryString($queryArguments);

        return $conf;
    }

    /**
     * @param array<mixed> $conf
     * @throws \JsonException
     */
    private function resolveContext(array $conf): ?TopwireContext
    {
        $topwireArguments = $this->resolveTopwireArguments($conf);
        if ($topwireArguments === null) {
            return null;
        }
        assert(in_array($topwireArguments['type'], ['plugin', 'contentElement', 'typoScript', 'context'], true));
        $contextRecordId = $this->contentObjectRenderer->currentRecord;
        if (isset($topwireArguments['tableName'], $topwireArguments['recordUid'])) {
            $contextRecordId = $topwireArguments['tableName'] . ':' . $topwireArguments['recordUid'];
        }
        $frontendController = $this->getTypoScriptFrontendController();
        $contextFactory = new TopwireContextFactory($frontendController);
        $context = match ($topwireArguments['type']) {
            'context' => $this->contentObjectRenderer->getRequest()->getAttribute('topwire') ?? throw new InvalidConfiguration('Topwire tye was set to "context", but no context could be resolved', 1676815069),
            'plugin' => $contextFactory->forPlugin($topwireArguments['extensionName'], $topwireArguments['pluginName'], $contextRecordId),
            'contentElement' => $contextFactory->forPath('tt_content', 'tt_content:' . $topwireArguments['uid']),
            'typoScript' => $contextFactory->forPath($topwireArguments['typoScriptPath'], $contextRecordId),
        };
        $frame = new Frame(
            baseId: $topwireArguments['frameId'] ?? 'link',
            wrapResponse: !empty($topwireArguments['wrapResponse']),
            scope: $context->scope,
        );
        return $context->withAttribute('frame', $frame);
    }

    /**
     * @param array<mixed> $conf
     * @return mixed[]|null
     * @throws \JsonException
     */
    private function resolveTopwireArguments(array $conf): ?array
    {
        if (isset($conf['topwire.'])) {
            return $this->ensureValidArguments($conf['topwire.']);
        }
        parse_str($conf['additionalParams'] ?? '', $queryArguments);
        $topwireArguments = $queryArguments[self::virtualLinkNamespace] ?? null;
        if (is_array($topwireArguments)) {
            return $this->ensureValidArguments($topwireArguments);
        }
        return null;
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
