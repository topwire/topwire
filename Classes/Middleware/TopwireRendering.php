<?php
namespace Topwire\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Topwire\ContentObject\TopwireContentObject;
use Topwire\Context\TopwireContext;
use Topwire\Exception\InvalidContentType;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Lightweight alternative to regular frontend requests, rendering only the provided context record/ plugin
 */
class TopwireRendering implements MiddlewareInterface
{
    private const defaultContentType = 'text/html';
    private const turboStreamContentType = 'text/vnd.turbo-stream.html';

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
        $contentTypeHeader = $response->getHeaderLine('Content-Type');
        $isStreamResponseAllowed = str_contains($request->getHeaderLine('Accept'), self::turboStreamContentType);
        if ($contentTypeHeader === ''
            || $response->getStatusCode() !== 200
            || !$request->hasHeader('Turbo-Frame')
            || str_starts_with($contentTypeHeader, self::defaultContentType)
            || ($isStreamResponseAllowed && str_starts_with($contentTypeHeader, self::turboStreamContentType))
        ) {
            return $response;
        }
        throw new InvalidContentType(
            sprintf(
                'Turbo frame requests must return content/type "%s",%s got "%s". '
                . 'Maybe forgot to add data-turbo="false" attribute for links leading to this error? '
                . 'Alternatively you can rewrite the current URL to have a file extension',
                self::defaultContentType,
                $isStreamResponseAllowed ? sprintf(' or "%s"', self::turboStreamContentType) : '',
                $contentTypeHeader
            ),
            1671308188
        );
    }
}
