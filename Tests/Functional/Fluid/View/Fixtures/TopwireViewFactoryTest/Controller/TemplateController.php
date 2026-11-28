<?php
declare(strict_types=1);
namespace Topwire\Tests\Functional\Fluid\View\Fixtures\TopwireViewFactoryTest\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Request;

class TemplateController extends ActionController
{
    public function renderTemplate(Request $request): string
    {
        $this->request = $request;
        $view = $this->resolveView();
        $view->assign('value', 'assigned');
        return $view->render();
    }
}
