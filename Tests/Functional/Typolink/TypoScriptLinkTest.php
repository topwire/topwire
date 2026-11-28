<?php
declare(strict_types=1);
namespace Topwire\Tests\Functional\Typolink;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Topwire\Context\ContextDenormalizer;
use Topwire\Context\TopwireContext;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TypoScriptLinkTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['topwire/topwire'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TypoScriptLinkTest/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TypoScriptLinkTest/contentElements.csv');
        $this->setUpFrontendRootPage(1, [
            'EXT:topwire/Tests/Functional/Typolink/Fixtures/TypoScriptLinkTest/setup.typoscript',
            'EXT:topwire/Tests/Functional/Typolink/Fixtures/TypoScriptLinkTest/linkConfigurations.typoscript',
        ]);
        $this->get(SiteWriter::class)->createNewBasicSite('main', 1, 'https://topwire.test/');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideTopwireLinks(): iterable
    {
        yield 'TypoScript configuration' => ['topwire-link'];
        yield 'query parameter configuration' => ['topwire-query-link'];
    }

    #[Test]
    #[DataProvider('provideTopwireLinks')]
    public function typoScriptGeneratesTopwireLinkToTextObject(string $linkId): void
    {
        $url = $this->renderLink($linkId, 'Load TEXT object');
        self::assertSame('https://topwire.test/', strtok($url, '?'));
        parse_str((string)parse_url($url, PHP_URL_QUERY), $queryParameters);
        self::assertArrayHasKey('tx_topwire', $queryParameters);
        self::assertIsString($queryParameters['tx_topwire']);
        $context = TopwireContext::fromUntrustedString($queryParameters['tx_topwire'], new ContextDenormalizer());

        self::assertSame('lib.fragment', $context->renderingPath->jsonSerialize());
        self::assertSame('pages', $context->contextRecord->tableName);
        self::assertSame(1, $context->contextRecord->id);
        self::assertSame(1, $context->contextRecord->pageId);
    }

    #[Test]
    public function typoScriptGeneratesPageLinkWithoutTopwireQueryParameters(): void
    {
        $url = $this->renderLink('page-link', 'Visit page');

        self::assertSame('https://topwire.test/', $url);
    }

    #[Test]
    public function queryParameterConfigurationGeneratesSameTopwireLink(): void
    {
        $configuredUrl = $this->renderLink('topwire-link', 'Load TEXT object');
        $queryParameterUrl = $this->renderLink('topwire-query-link', 'Load TEXT object');

        self::assertSame($configuredUrl, $queryParameterUrl);
    }

    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function provideTopwireLinkTypes(): iterable
    {
        yield 'plugin' => ['plugin', '<em>Plugin target</em>'];
        yield 'contentElement' => ['contentElement', '<p>Content element target</p>'];
        yield 'typoScript' => ['typoScript', '<strong>Topwire TEXT &amp; content</strong>'];
        yield 'context' => ['context', null];
    }

    #[Test]
    #[DataProvider('provideTopwireLinkTypes')]
    public function typoScriptConfigurationTakesPrecedenceOverQueryParameters(string $type, ?string $expectedBody): void
    {
        $sourceUrl = $this->renderLink('topwire-configuration-links', 'Link configurations');
        $sourceResponse = $this->executeFrontendSubRequest(new InternalRequest($sourceUrl));
        self::assertSame(200, $sourceResponse->getStatusCode());
        $sourceBody = (string)$sourceResponse->getBody();
        $configuredUrl = $this->extractLink($sourceBody, $type . '-configured', 'Select target');
        $queryParameterUrl = $this->extractLink($sourceBody, $type . '-query', 'Select target');
        $combinedUrl = $this->extractLink($sourceBody, $type . '-combined', 'Select target');

        self::assertNotSame($configuredUrl, $queryParameterUrl);
        self::assertSame($configuredUrl, $combinedUrl);

        $response = $this->executeFrontendSubRequest(new InternalRequest($combinedUrl));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($expectedBody ?? $sourceBody, (string)$response->getBody());
    }

    #[Test]
    #[DataProvider('provideTopwireLinks')]
    public function followingGeneratedLinkReturnsOnlyRenderedTextObject(string $linkId): void
    {
        $url = $this->renderLink($linkId, 'Load TEXT object');

        $response = $this->executeFrontendSubRequest(new InternalRequest($url));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<strong>Topwire TEXT &amp; content</strong>', (string)$response->getBody());
    }

    private function renderLink(string $linkId, string $linkText): string
    {
        $response = $this->executeFrontendSubRequest(new InternalRequest('https://topwire.test/'));
        self::assertSame(200, $response->getStatusCode());
        $body = (string)$response->getBody();
        self::assertStringContainsString('<h1>Full page</h1>', $body);
        self::assertStringContainsString('<div>Full page footer</div>', $body);

        return $this->extractLink($body, $linkId, $linkText);
    }

    private function extractLink(string $body, string $linkId, string $linkText): string
    {
        $document = new \DOMDocument();
        self::assertTrue($document->loadHTML($body));
        $links = (new \DOMXPath($document))->query('//a[@id="' . $linkId . '"]');
        self::assertInstanceOf(\DOMNodeList::class, $links);
        self::assertCount(1, $links);
        $link = $links->item(0);
        self::assertInstanceOf(\DOMElement::class, $link);
        self::assertSame($linkText, $link->textContent);

        return $link->getAttribute('href');
    }
}
