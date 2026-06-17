<?php
namespace Topwire\EventListener;

use Topwire\ContentObject\Exception\InvalidTableContext;
use Topwire\Context\TopwireContext;
use Topwire\Context\TopwireContextFactory;
use Topwire\Turbo\Frame;
use Topwire\Turbo\FrameOptions;
use Topwire\Turbo\FrameRenderer;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterStdWrapFunctionsExecutedEvent;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;

#[AsEventListener('topwire.contentElementWrap')]
class ContentElementWrapListener
{
    public function __invoke(AfterStdWrapFunctionsExecutedEvent $event): void
    {
        $configuration = $event->getConfiguration();
        if ((int)$event->getContentObjectRenderer()->stdWrapValue('turboFrameWrap', $configuration, 0) === 0) {
            return;
        }
        if ($event->getContentObjectRenderer()->getRequest()->getAttribute('topwire') instanceof TopwireContext) {
            // Frame wrap is done by TOPWIRE content object automatically
            return;
        }
        if ($event->getContentObjectRenderer()->getCurrentTable() !== 'tt_content') {
            throw new InvalidTableContext('"stdWrap.turboFrameWrap" can only be used for table "tt_content"', 1671124640);
        }

        $path = $event->getConfiguration()['turboFrameWrap.']['path'] ?? $this->determineRenderingPath($event->getContentObjectRenderer(), $configuration);

        $contextFactory = new TopwireContextFactory($event->getContentObjectRenderer()->getRequest());
        $context = $contextFactory->forPath($path, $event->getContentObjectRenderer()->currentRecord);
        $scopeFrame = (bool)$event->getContentObjectRenderer()->stdWrapValue('scopeFrame', $configuration['turboFrameWrap.'] ?? [], 1);
        $frameId = $event->getContentObjectRenderer()->stdWrapValue('frameId', $configuration['turboFrameWrap.'] ?? [], null);
        $propagateUrl = (bool)$event->getContentObjectRenderer()->stdWrapValue('propagateUrl', $configuration['turboFrameWrap.'] ?? [], 0);
        $frame = new Frame(
            baseId: (string)($frameId ?? $event->getContentObjectRenderer()->currentRecord),
            wrapResponse: true,
            scope: $scopeFrame ? $context->scope : null,
            renderFullDocument: $propagateUrl,
        );
        $showWhenFrameMatches = (bool)$event->getContentObjectRenderer()->stdWrapValue('showWhenFrameMatches', $configuration['turboFrameWrap.'] ?? [], false);
        $requestedFrame = $event->getContentObjectRenderer()->getRequest()->getAttribute('topwireFrame');
        if ($scopeFrame
            && $showWhenFrameMatches
            && (
                !$requestedFrame instanceof Frame
                || $requestedFrame->id !== $frame->id
            )
        ) {
            return;
        }
        $context = $context->withAttribute('frame', $frame);
        $event->setContent((new FrameRenderer())->render(
            frame: $frame,
            content: $event->getContent() ?? '',
            options: new FrameOptions(
                propagateUrl: $propagateUrl,
                morph: (bool)$event->getContentObjectRenderer()->stdWrapValue('morph', $configuration['turboFrameWrap.'] ?? [], 0),
            ),
            context: $scopeFrame ? $context : null,
        ));
    }

    /**
     * @param array<mixed> $configuration
     * @throws InvalidTableContext
     * @throws ContentRenderingException
     */
    private function determineRenderingPath(ContentObjectRenderer $cObj, array $configuration): string
    {
        $frontendTypoScript = $cObj->getRequest()->getAttribute('frontend.typoscript');
        $setup = $frontendTypoScript?->getSetupArray() ?? [];

        if (!isset($setup['tt_content'], $configuration['turboFrameWrap.'])) {
            throw new InvalidTableContext('"stdWrap.turboFrameWrap" can only be used for table "tt_content", typoscript setup missing!', 1687873940);
        }
        $frameWrapConfig = $configuration['turboFrameWrap.'];
        $paths = [
            'tt_content.',
            'tt_content./' . $cObj->data['CType'] . '.',
            'tt_content./' . $cObj->data['CType'] . './20.',
        ];
        if ($cObj->data['CType'] === 'list') {
            $paths[] = 'tt_content./' . $cObj->data['CType'] . './20./' . $cObj->data['list_type'] . '.';
        }
        foreach ($paths as $path) {
            try {
                $potentialWrapConfig = ArrayUtility::getValueByPath($setup, $path . '/stdWrap./turboFrameWrap.');
                if ($potentialWrapConfig === $frameWrapConfig) {
                    return rtrim(str_replace('./', '.', $path), '.');
                }
            } catch (MissingArrayPathException $e) {
                $potentialWrapConfig = [];
            }
        }
        return 'tt_content';
    }
}
