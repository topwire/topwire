<?php
declare(strict_types=1);
namespace Helhum\Topwire\ViewHelpers\Context;

use Helhum\Topwire\ContentObject\TopwireContentObject;
use Helhum\Topwire\Context\ContextStack;
use Helhum\Topwire\Context\Exception\InvalidTopwireContext;
use Helhum\Topwire\Context\TopwireContext;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class SlotViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    /**
     * @param array<mixed> $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @throws \JsonException
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $context = (new ContextStack($renderingContext->getViewHelperVariableContainer()))->current();
        if (!$context instanceof TopwireContext) {
            throw new InvalidTopwireContext('Can only render as child of a Topwire context view helper', 1671623956);
        }
        assert($renderingContext instanceof RenderingContext);
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->start([], $context->contextRecord->tableName, $renderingContext->getRequest());
        $contentObjectRenderer->currentRecord = $context->contextRecord->tableName . ':' . $context->contextRecord->id;
        return $contentObjectRenderer
            ->cObjGetSingle(
                TopwireContentObject::NAME,
                [
                    'context' => $context,
                ]
            );
    }
}
