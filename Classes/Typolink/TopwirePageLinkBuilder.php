<?php
declare(strict_types=1);
namespace Topwire\Typolink;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\CMS\Frontend\Typolink\PageLinkBuilder;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;

#[Autoconfigure(public: true)]
class TopwirePageLinkBuilder extends PageLinkBuilder
{
    /**
     * @param array<mixed> $linkDetails
     * @param array<mixed> $configuration
     * @throws UnableToLinkException
     */
    public function buildLink(array $linkDetails, array $configuration, ServerRequestInterface $request, string $linkText = ''): LinkResultInterface
    {
        $defaultLinkBuilderClass = $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder']['overriddenDefault'] ?? null;
        if (is_string($defaultLinkBuilderClass)
            && is_subclass_of($defaultLinkBuilderClass, AbstractTypolinkBuilder::class)
        ) {
            $originalPageLinkBuilder = GeneralUtility::makeInstance($defaultLinkBuilderClass);
        } else {
            $originalPageLinkBuilder = null;
        }
        $pageLinkContext = new TopwirePageLinkContext($request->getAttribute('currentContentObject'));

        $linkDetails['topwirePageLinkContext'] = $pageLinkContext;
        if (isset($originalPageLinkBuilder)) {
            return $originalPageLinkBuilder->buildLink($linkDetails, $configuration, $request, $linkText);
        }
        return parent::buildLink($linkDetails, $configuration, $request, $linkText);
    }
}
