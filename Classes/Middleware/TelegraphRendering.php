<?php
namespace Helhum\TYPO3\Telegraph\Middleware;

use Helhum\TYPO3\Telegraph\ContentObject\TelegraphContentObject;
use Helhum\TYPO3\Telegraph\RenderingContext\RenderingContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Lightweight alternative to regular frontend requests, rendering only the provided context record/ plugin
 */
class TelegraphRendering implements MiddlewareInterface
{
    private const defaultContentType = 'text/html';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $frontendController = $GLOBALS['TSFE'];
        $requestedContentType = $frontendController->config['config']['contentType'] ?? self::defaultContentType;
        $renderingConfig = $this->extractRenderingArgumentsFromRequest($request);
        if (!isset($renderingConfig) || !$frontendController->isGeneratePage()) {
            return $this->amendContentType($handler->handle($request), $requestedContentType);
        }

        $frontendController->config['config']['debug'] = 0;
        $frontendController->config['config']['disableAllHeaderCode'] = 1;
        $frontendController->config['config']['disableCharsetHeader'] = 0;
        $frontendController->pSetup = [
            '10' => TelegraphContentObject::NAME,
            '10.' => $renderingConfig,
        ];

        return $this->amendContentType($handler->handle($request), $requestedContentType);
    }

    /**
     * @param ServerRequestInterface $request
     * @return array{context: RenderingContext}|null
     */
    private function extractRenderingArgumentsFromRequest(ServerRequestInterface $request): ?array
    {
        $renderingContext = $request->getAttribute('telegraph');
        if ($renderingContext instanceof RenderingContext) {
            return [
                'context' => $renderingContext,
            ];
        }

        return null;
    }

    /**
     * TYPO3's frontend rendering allows to influence the content type,
     * but does not store this information in cache, which leads to wrong content type
     * to be sent when content if pulled from cache.
     * We add a tiny workaround, that allows plugins to set the content type, but also
     * store the content type in cache:
     *
     * $GLOBALS['TSFE']->setContentType('application/json');
     * $GLOBALS['TSFE']->config['config']['contentType'] = 'application/json';
     *
     * @param ResponseInterface $response
     * @param string $requestedContentType
     * @return ResponseInterface
     */
    private function amendContentType(ResponseInterface $response, string $requestedContentType): ResponseInterface
    {
        if ($response->getStatusCode() !== 200
            || !$response->hasHeader('Content-Type')
        ) {
            return $response;
        }
        $originalContentTypeHeader = $response->getHeader('Content-Type')[0];
        if (strpos($originalContentTypeHeader, self::defaultContentType) !== 0
            || strpos($originalContentTypeHeader, $requestedContentType) === 0
        ) {
            return $response;
        }
        return $response->withHeader('Content-Type', \str_replace(self::defaultContentType, $requestedContentType, $originalContentTypeHeader));
    }
}
