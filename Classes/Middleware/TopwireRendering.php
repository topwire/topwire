<?php
namespace Helhum\Topwire\Middleware;

use Helhum\Topwire\ContentObject\TopwireContentObject;
use Helhum\Topwire\Context\TopwireContext;
use Helhum\Topwire\Exception\InvalidContentType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Lightweight alternative to regular frontend requests, rendering only the provided context record/ plugin
 */
class TopwireRendering implements MiddlewareInterface
{
    private const defaultContentType = 'text/html';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $frontendController = $request->getAttribute('frontend.controller');
        assert($frontendController instanceof TypoScriptFrontendController);
        $context = $request->getAttribute('topwire');
        if (!$context instanceof TopwireContext || !$frontendController->isGeneratePage()) {
            return $this->validateContentType($request, $handler->handle($request));
        }

        $frontendController->config['config']['debug'] = 0;
        $frontendController->config['config']['disableAllHeaderCode'] = 1;
        $frontendController->config['config']['disableCharsetHeader'] = 0;
        $frontendController->pSetup = [
            '10' => TopwireContentObject::NAME,
            '10.' => [
                'context' => $context,
            ],
        ];

        return $this->validateContentType($request, $handler->handle($request));
    }

    private function validateContentType(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$request->hasHeader('Turbo-Frame')
            || $response->getStatusCode() !== 200
            || !$response->hasHeader('Content-Type')
        ) {
            return $response;
        }
        $contentTypeHeader = $response->getHeaderLine('Content-Type');
        if (!str_starts_with($contentTypeHeader, self::defaultContentType)) {
            throw new InvalidContentType(
                sprintf(
                    'Turbo requests must return content/type "text/html", got "%s". '
                    . 'Maybe forgot to add data-turbo="false" attribute for links leading to this error? '
                    . 'Alternatively you can rewrite the current URL to have a file extension',
                    $contentTypeHeader
                ),
                1671308188
            );
        }
        return $response;
    }
}
