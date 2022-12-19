<?php
declare(strict_types=1);
namespace Helhum\Topwire\Context;

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

    public function jsonSerialize(): string
    {
        return $this->renderingPath;
    }
}
