<?php
declare(strict_types=1);
namespace Topwire\Fluid\View;

use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;

final readonly class TopwireViewFactory implements ViewFactoryInterface
{
    public function __construct(private RenderingContextFactory $renderingContextFactory)
    {
    }

    public function create(ViewFactoryData $data): ViewInterface
    {
        $renderingContext = $this->renderingContextFactory->create([
            'templateRootPaths' => $data->templateRootPaths ?? [],
            'partialRootPaths' => $data->partialRootPaths ?? [],
            'layoutRootPaths' => $data->layoutRootPaths ?? [],
        ], $data->request);
        if ($data->templatePathAndFilename !== null) {
            $renderingContext->getTemplatePaths()->setTemplatePathAndFilename($data->templatePathAndFilename);
        }
        if ($data->format !== null) {
            $renderingContext->getTemplatePaths()->setFormat($data->format);
        }
        return new FluidViewAdapter(new TopwireTemplateView($renderingContext));
    }
}
