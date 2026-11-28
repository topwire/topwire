<?php
declare(strict_types=1);
namespace Topwire\Tests\Functional\Fluid\View;

use PHPUnit\Framework\Attributes\Test;
use Topwire\Context\Attribute\Section;
use Topwire\Context\ContextRecord;
use Topwire\Context\RenderingPath;
use Topwire\Context\TopwireContext;
use Topwire\Tests\Functional\Fluid\View\Fixtures\TopwireViewFactoryTest\Controller\TaggedController;
use Topwire\Tests\Functional\Fluid\View\Fixtures\TopwireViewFactoryTest\Controller\TemplateController;
use Topwire\Turbo\Frame;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TopwireViewFactoryTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $testExtensionsToLoad = [
        'topwire/topwire',
        __DIR__ . '/Fixtures/TopwireViewFactoryTest/Extensions/topwire_view_factory_fixture',
    ];

    #[Test]
    public function taggedControllerRendersCompleteTemplateWithoutTopwireContext(): void
    {
        $controller = $this->createController(TaggedController::class);

        self::assertSame('Full template: assigned', $controller->renderTemplate($this->createRequest()));
    }

    #[Test]
    public function taggedControllerRendersRequestedSection(): void
    {
        $controller = $this->createController(TaggedController::class);
        $context = $this->createContext()->withAttribute('section', new Section('Detail'));

        self::assertSame('Detail: assigned', $controller->renderTemplate($this->createRequest($context)));
    }

    #[Test]
    public function taggedControllerRendersSectionMatchingFrame(): void
    {
        $controller = $this->createController(TaggedController::class);
        $context = $this->createContext()->withAttribute('frame', new Frame('message-detail', false, null));

        self::assertSame('Frame: assigned', $controller->renderTemplate($this->createRequest($context)));
    }

    #[Test]
    public function taggedControllerRendersCompleteTemplateWhenFrameSectionIsMissing(): void
    {
        $controller = $this->createController(TaggedController::class);
        $context = $this->createContext()->withAttribute('frame', new Frame('missing', false, null));

        self::assertSame('Full template: assigned', $controller->renderTemplate($this->createRequest($context)));
    }

    #[Test]
    public function untaggedControllerRendersCompleteTemplateWithSectionContext(): void
    {
        $controller = $this->createController(TemplateController::class);
        $context = $this->createContext()->withAttribute('section', new Section('Detail'));

        self::assertSame('Full template: assigned', $controller->renderTemplate($this->createRequest($context)));
    }

    /**
     * @param class-string<TemplateController> $className
     */
    private function createController(string $className): TemplateController
    {
        $configurationManager = $this->get(ConfigurationManagerInterface::class);
        $configurationManager->setRequest($this->createRequest());
        $configurationManager->setConfiguration([
            'view.' => [
                'templateRootPaths.' => [__DIR__ . '/Fixtures/TopwireViewFactoryTest/Templates/'],
                'layoutRootPaths.' => [__DIR__ . '/Fixtures/TopwireViewFactoryTest/Layouts/'],
            ],
        ]);
        return $this->get($className);
    }

    private function createRequest(?TopwireContext $context = null): Request
    {
        $parameters = new ExtbaseRequestParameters();
        $parameters->setControllerExtensionName('Topwire');
        $parameters->setControllerName('Template');
        $parameters->setControllerActionName('index');
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setSetupArray([]);
        $request = (new ServerRequest('https://topwire.test/'))
            ->withAttribute('applicationType', 1)
            ->withAttribute('frontend.typoscript', $typoScript)
            ->withAttribute('extbase', $parameters);
        if ($context !== null) {
            $request = $request->withAttribute('topwire', $context);
        }
        return new Request($request);
    }

    private function createContext(): TopwireContext
    {
        return new TopwireContext(new RenderingPath('lib.plugin'), new ContextRecord('tt_content', 1, 1));
    }
}
