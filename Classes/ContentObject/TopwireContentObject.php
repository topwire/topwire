<?php
namespace Helhum\Topwire\ContentObject;

use Helhum\Topwire\Context\TopwireContext;
use Helhum\Topwire\Turbo\Frame;
use Helhum\Topwire\Turbo\FrameOptions;
use Helhum\Topwire\Turbo\FrameRenderer;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class TopwireContentObject extends AbstractContentObject
{
    public const NAME = 'TOPWIRE';

    /**
     * @param array<mixed> $conf
     * @return string
     */
    public function render($conf = []): string
    {
        $context = $conf['context'];
        assert($context instanceof TopwireContext);
        $content = $this->renderContentWithoutRecursion($context);
        $frame = $context->getAttribute('frame');
        if (!$frame instanceof Frame
            || !$frame->wrapResponse
        ) {
            // The frame id is known and set during partial rendering
            // At the same time the rendered content already contains this id, so the frame is wrapped already
            return $content;
        }

        return (new FrameRenderer())->render(
            frame: $frame,
            content: $content,
            options: new FrameOptions(),
            context: $context,
        );
    }

    private function renderContentWithoutRecursion(TopwireContext $context): string
    {
        $actionRecursionPrefix = $context->getAttribute('plugin')?->actionName ?? null;
        $frontendController = $this->request?->getAttribute('frontend.controller');
        if (!isset($actionRecursionPrefix)
            || !$frontendController instanceof TypoScriptFrontendController
        ) {
            // Use default recursion handling of TYPO3
            return $this->cObj->cObjGetSingle(
                'RECORDS',
                $this->transformToRecordsConfiguration($context)
            );
        }
        // Prevent recursion, but allow rendering of the same plugin with a different action
        // @see CONTENT and RECORDS content objects
        $currentlyRenderingRecordId = $frontendController->currentRecord;
        $requestedRenderingRecordId = $actionRecursionPrefix . $context->contextRecord->tableName . ':' . $context->contextRecord->id;
        if (isset($frontendController->recordRegister[$requestedRenderingRecordId])) {
            return '';
        }
        $frontendController->currentRecord = $requestedRenderingRecordId;
        $content = $this->cObj->cObjGetSingle(
            'RECORDS',
            $this->transformToRecordsConfiguration($context)
        );
        $frontendController->currentRecord = $currentlyRenderingRecordId;

        return $content;
    }

    /**
     * @param TopwireContext $context
     * @return array<string, mixed>
     */
    private function transformToRecordsConfiguration(TopwireContext $context): array
    {
        return [
            'source' => $context->contextRecord->tableName . '_' . $context->contextRecord->id,
            'tables' => $context->contextRecord->tableName,
            'conf.' => [
                $context->contextRecord->tableName => '< ' . $context->renderingPath->jsonSerialize(),
            ],
        ];
    }
}
