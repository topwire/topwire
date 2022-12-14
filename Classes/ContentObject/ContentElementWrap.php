<?php
namespace Helhum\TYPO3\Telegraph\ContentObject;

use Helhum\TYPO3\Telegraph\ContentObject\Exception\InvalidTableContext;
use Helhum\TYPO3\Telegraph\RenderingContext\RenderingContextFactory;
use Helhum\TYPO3\Telegraph\Turbo\FrameOptions;
use Helhum\TYPO3\Telegraph\Turbo\FrameRenderer;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectStdWrapHookInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class ContentElementWrap implements ContentObjectStdWrapHookInterface
{
    /**
     * @param string $content
     * @param array<mixed> $configuration
     * @param ContentObjectRenderer $parentObject
     * @return string
     */
    public function stdWrapPreProcess($content, array $configuration, ContentObjectRenderer &$parentObject)
    {
        return $content;
    }

    /**
     * @param string $content
     * @param array<mixed> $configuration
     * @param ContentObjectRenderer $parentObject
     * @return string
     */
    public function stdWrapOverride($content, array $configuration, ContentObjectRenderer &$parentObject)
    {
        return $content;
    }

    /**
     * @param string $content
     * @param array<mixed> $configuration
     * @param ContentObjectRenderer $parentObject
     * @return string
     */
    public function stdWrapProcess($content, array $configuration, ContentObjectRenderer &$parentObject)
    {
        return $content;
    }

    /**
     * @param string $content
     * @param array<mixed> $configuration
     * @param ContentObjectRenderer $parentObject
     * @return string
     */
    public function stdWrapPostProcess($content, array $configuration, ContentObjectRenderer &$parentObject)
    {
        if (
            empty($configuration['telegraphFrameWrap'])
            && (
                empty($configuration['telegraphFrameWrap.'])
                || empty($parentObject->stdWrapValue('telegraphFrameWrap', $configuration))
            )
        ) {
            return $content;
        }
        if ($parentObject->getCurrentTable() !== 'tt_content') {
            throw new InvalidTableContext('"stdWrap.telegraphFrameWrap" can only be used for table "tt_content"', 1671124640);
        }

        $controller = $parentObject->getTypoScriptFrontendController();
        assert($controller instanceof TypoScriptFrontendController);

        $path = 'tt_content.' . $parentObject->data['CType'] . '.20';
        if ($parentObject->data['CType'] === 'list') {
            $path .= '.' . $parentObject->data['list_type'];
        }
        $record = $parentObject->currentRecord;

        $contextFactory = new RenderingContextFactory($controller);
        return (new FrameRenderer())->render(
            $contextFactory->forPath($path, $record),
            $content,
            new FrameOptions(
                id: 'tt_content',
                propagateUrl: false,
            )
        );
    }
}
