<?php
declare(strict_types=1);
namespace Topwire\Tests\Unit\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Topwire\Context\ContextRecord;
use Topwire\Context\ContextStack;
use Topwire\Context\RenderingPath;
use Topwire\Context\TopwireContext;
use Topwire\ViewHelpers\Context\ContentElementViewHelper;
use Topwire\ViewHelpers\Context\PluginViewHelper;
use Topwire\ViewHelpers\ContextViewHelper;
use Topwire\ViewHelpers\Turbo\FrameViewHelper;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class ContextCleanupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TCA']['pages'] = [];
        $GLOBALS['TCA']['tt_content'] = [];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'test-encryption-key';
        $extensionService = self::createStub(ExtensionService::class);
        $extensionService->method('getPluginNamespace')->willReturn('tx_example_plugin');
        GeneralUtility::setSingletonInstance(ExtensionService::class, $extensionService);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{class-string<AbstractViewHelper>, bool}>
     */
    public static function provideViewHelpers(): iterable
    {
        foreach ([ContentElementViewHelper::class, ContextViewHelper::class, PluginViewHelper::class, FrameViewHelper::class] as $className) {
            yield $className . ' success' => [$className, false];
            yield $className . ' exception' => [$className, true];
        }
    }

    /**
     * @param class-string<AbstractViewHelper> $className
     */
    #[DataProvider('provideViewHelpers')]
    public function testRestoresParentContext(string $className, bool $throw): void
    {
        $stack = new ContextStack();
        $parent = new TopwireContext(new RenderingPath('lib.parent'), new ContextRecord('pages', 1, 1));
        $stack->push($parent);
        $viewHelper = $this->createViewHelper($className, $stack, $this->createRenderingContext());

        $this->renderWithOutcome($viewHelper, $throw, static function () use ($stack, $parent): void {
            self::assertNotSame($parent, $stack->current());
        });

        self::assertSame($parent, $stack->current());
        $stack->pop();
        self::assertNull($stack->current());
    }

    /**
     * @param class-string<AbstractViewHelper> $className
     */
    #[DataProvider('provideViewHelpers')]
    public function testLeavesFollowingFrameUnscoped(string $className, bool $throw): void
    {
        $stack = new ContextStack();
        $renderingContext = $this->createRenderingContext();
        $viewHelper = $this->createViewHelper($className, $stack, $renderingContext);
        $this->renderWithOutcome($viewHelper, $throw);

        $frame = $this->createViewHelper(FrameViewHelper::class, $stack, $renderingContext);
        $frame->setRenderChildrenClosure(static fn (): string => 'following frame');

        self::assertSame('<turbo-frame id="inner">following frame</turbo-frame>', $frame->render());
        self::assertNull($stack->current());
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function provideOutcomes(): iterable
    {
        yield 'success' => [false];
        yield 'exception' => [true];
    }

    #[DataProvider('provideOutcomes')]
    public function testRestoresRenderingContextRequest(bool $throw): void
    {
        $stack = new ContextStack();
        $renderingContext = $this->createRenderingContext();
        $request = $renderingContext->getAttribute(ServerRequestInterface::class);
        $viewHelper = $this->createViewHelper(PluginViewHelper::class, $stack, $renderingContext);

        $this->renderWithOutcome($viewHelper, $throw, static function () use ($renderingContext, $request, $stack): void {
            $pluginRequest = $renderingContext->getAttribute(ServerRequestInterface::class);
            self::assertNotSame($request, $pluginRequest);
            self::assertSame($stack->current(), $pluginRequest->getAttribute('topwire'));
        });

        self::assertSame($request, $renderingContext->getAttribute(ServerRequestInterface::class));
    }

    #[DataProvider('provideOutcomes')]
    public function testRestoresContentObjectRequest(bool $throw): void
    {
        $stack = new ContextStack();
        $renderingContext = $this->createRenderingContext();
        $request = $renderingContext->getAttribute(ServerRequestInterface::class);
        $contentObject = $this->getMockBuilder(ContentObjectRenderer::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $request = $request->withAttribute('currentContentObject', $contentObject);
        $contentObject->setRequest($request);
        $renderingContext->setAttribute(ServerRequestInterface::class, $request);
        $viewHelper = $this->createViewHelper(PluginViewHelper::class, $stack, $renderingContext);

        $this->renderWithOutcome($viewHelper, $throw, static function () use ($renderingContext, $contentObject, $stack): void {
            self::assertSame($stack->current(), $contentObject->getRequest()->getAttribute('topwire'));
            self::assertSame($renderingContext->getAttribute(ServerRequestInterface::class), $contentObject->getRequest());
        });

        self::assertSame($request, $contentObject->getRequest());
        self::assertSame($request, $renderingContext->getAttribute(ServerRequestInterface::class));
    }

    private function createRenderingContext(): RenderingContext
    {
        $renderingContext = $this->getMockBuilder(RenderingContext::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $renderingContext->setAttribute(ServerRequestInterface::class, (new ServerRequest())->withAttribute('routing', new PageArguments(1, '0', [])));
        return $renderingContext;
    }

    /**
     * @param class-string<AbstractViewHelper> $className
     */
    private function createViewHelper(string $className, ContextStack $stack, RenderingContext $renderingContext): AbstractViewHelper
    {
        $viewHelper = new $className($stack);
        $viewHelper->setRenderingContext($renderingContext);
        $arguments = [];
        foreach ($viewHelper->prepareArguments() as $name => $definition) {
            $arguments[$name] = $definition->getDefaultValue();
        }
        $viewHelper->setArguments(array_replace($arguments, match ($className) {
            ContentElementViewHelper::class => ['uid' => 2],
            ContextViewHelper::class => ['typoScriptPath' => 'tt_content', 'recordUid' => 2, 'tableName' => 'tt_content'],
            PluginViewHelper::class => ['extensionName' => 'Example', 'pluginName' => 'Plugin'],
            FrameViewHelper::class => ['id' => 'inner'],
            default => [],
        }));
        return $viewHelper;
    }

    private function renderWithOutcome(AbstractViewHelper $viewHelper, bool $throw, ?\Closure $assertDuringRendering = null): void
    {
        $exception = $throw ? new \RuntimeException('Child rendering failed', 1795604400) : null;
        $viewHelper->setRenderChildrenClosure(static function () use ($exception, $assertDuringRendering): string {
            $assertDuringRendering?->__invoke();
            if ($exception !== null) {
                throw $exception;
            }
            return 'children';
        });
        try {
            self::assertStringContainsString('children', $viewHelper->render());
        } catch (\RuntimeException $caught) {
            self::assertSame($exception, $caught);
            return;
        }
        if ($exception !== null) {
            self::fail('The child rendering exception was swallowed.');
        }
    }
}
