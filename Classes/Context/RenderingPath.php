<?php
declare(strict_types=1);
namespace Helhum\Topwire\Context;

use Helhum\Topwire\Context\Exception\InvalidTopwireContext;

class RenderingPath implements \JsonSerializable
{
    public function __construct(private readonly string $renderingPath)
    {
    }

    /**
     * @param string $extensionName
     * @param string $pluginName
     * @param array<string, mixed> $contentRenderingConfig
     * @return self
     */
    public static function fromPlugin(string $extensionName, string $pluginName, array $contentRenderingConfig): self
    {
        $pluginSignature = strtolower(str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName))) . '_' . $pluginName);
        if (isset($contentRenderingConfig[$pluginSignature . '.']['20'])) {
            return new self(sprintf('tt_content.%s.20', $pluginSignature));
        }
        if (isset($contentRenderingConfig['list.']['20.'][$pluginSignature])) {
            return new self(sprintf('tt_content.list.20.%s', $pluginSignature));
        }
        return new self('tt_content');
    }

    public static function fromJson(string $json): self
    {
        $path = \json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_string($path)) {
            throw new InvalidTopwireContext('Could not decode context record', 1671024039);
        }

        return new self(renderingPath: $path);
    }

    public function jsonSerialize(): string
    {
        return $this->renderingPath;
    }
}
