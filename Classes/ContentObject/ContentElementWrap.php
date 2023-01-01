<?php
namespace Helhum\Topwire\ContentObject;

use Helhum\Topwire\ContentObject\Exception\InvalidTableContext;
use Helhum\Topwire\Context\TopwireContextFactory;
use Helhum\Topwire\Turbo\Frame;
use Helhum\Topwire\Turbo\FrameOptions;
use Helhum\Topwire\Turbo\FrameRenderer;
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
            empty($configuration['turboFrameWrap'])
            && (
                empty($configuration['turboFrameWrap.'])
                || empty($parentObject->stdWrapValue('turboFrameWrap', $configuration))
            )
        ) {
            return $content;
        }
        if ($parentObject->getRequest()->hasHeader('Topwire-Context')) {
            // Frame wrap is done by middleware automatically
            return $content;
        }
        if ($parentObject->getCurrentTable() !== 'tt_content') {
            throw new InvalidTableContext('"stdWrap.turboFrameWrap" can only be used for table "tt_content"', 1671124640);
        }
        $controller = $parentObject->getTypoScriptFrontendController();
        assert($controller instanceof TypoScriptFrontendController);

        $path = 'tt_content.' . $parentObject->data['CType'] . '.20';
        if ($parentObject->data['CType'] === 'list') {
            $path .= '.' . $parentObject->data['list_type'];
        }
        $record = $parentObject->currentRecord;

        $contextFactory = new TopwireContextFactory($controller);
        $context = $contextFactory->forPath($path, $record);
        $frame = new Frame(
            baseId: $parentObject->currentRecord,
            wrapResponse: true,
            scope: $context->scope,
        );
        $context = $context->withAttribute('frame', $frame);
        return (new FrameRenderer())->render(
            frame: $frame,
            content: $content,
            options: new FrameOptions(
                propagateUrl: (bool)$parentObject->stdWrapValue('propagateUrl', $configuration['turboFrameWrap.'] ?? [], 0),
            ),
            context: $context,
        );
    }
}
