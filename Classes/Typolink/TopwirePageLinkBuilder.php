<?php
declare(strict_types=1);
namespace Helhum\Topwire\Typolink;

use Helhum\Topwire\Context\TopwireContextFactory;
use Helhum\Topwire\Exception\InvalidConfiguration;
use Helhum\Topwire\Turbo\Frame;
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
     * @param string $linkText
     * @param string $target
     * @param array<mixed> $conf
     * @return LinkResultInterface
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
        if (!isset($conf['additionalParams'])) {
            return $conf;
        }
        parse_str($conf['additionalParams'], $queryArguments);
        $topwireArguments = $queryArguments[self::linkNamespace] ?? null;
        if (!isset($topwireArguments) || !is_array($topwireArguments)) {
            return $conf;
        }
        unset($queryArguments[self::linkNamespace]);

        $this->ensureValidArguments($topwireArguments);
        $contextRecordId = $this->contentObjectRenderer->currentRecord;
        if (isset($topwireArguments['contextTableName'], $topwireArguments['contextTableNameUid'])) {
            $contextRecordId = $topwireArguments['contextTableName'] . ':' . $topwireArguments['contextTableNameUid'];
        }
        $frontendController = $this->getTypoScriptFrontendController();
        $contextFactory = new TopwireContextFactory($frontendController);
        $config = match ($topwireArguments['type']) {
            'plugin' => $contextFactory->forPlugin($topwireArguments['extensionName'], $topwireArguments['pluginName'], $contextRecordId),
            'contentElement' => $contextFactory->forPath('tt_content', 'tt_content:' . $topwireArguments['contentElementUid']),
            'typoScript' => $contextFactory->forPath($topwireArguments['typoScriptPath'], $contextRecordId),
        };
        if (isset($config)) {
            $queryArguments[self::linkNamespace] = (new Frame('link', $config))->toHashedString();
        }
        $conf['additionalParams'] = '&' . HttpUtility::buildQueryString($queryArguments);

        return $conf;
    }

    /**
     * @param array<mixed> $topwireArguments
     * @throws InvalidConfiguration
     */
    private function ensureValidArguments(array $topwireArguments): void
    {
        try {
            match ($topwireArguments['type'] ?? null) {
                'plugin' => !isset($topwireArguments['extensionName'], $topwireArguments['pluginName'])
                    && throw new InvalidConfiguration('URLs of type "plugin" must have "extensionName" and "pluginName" set', 1671558884),
                'contentElement' => !isset($topwireArguments['contentElementUid'])
                    && throw new InvalidConfiguration('URLs of type "contentElement" must have "contentElementUid" set', 1671560042),
                'typoScript' => !isset($topwireArguments['contentElementUid'])
                    && throw new InvalidConfiguration('URLs of type "typoScript" must have "typoScriptPath" set', 1671558886),
            };
        } catch (\UnhandledMatchError $e) {
            throw new InvalidConfiguration('URL type must be set and must be either "plugin", "contentElement" or "typoScript"', 1671560111, $e);
        }
    }
}
