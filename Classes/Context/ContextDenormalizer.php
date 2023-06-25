<?php
declare(strict_types=1);
namespace Helhum\Topwire\Context;

use Helhum\Topwire\Turbo\Frame;

class ContextDenormalizer
{
    private const attributeMap = [
        'frame' => Frame::class,
    ];

    /**
     * @param array{renderingPath: string, contextRecord: array{tableName: string, id: int, pageId: int}, attributes?: array<string, mixed>} $data
     * @throws Exception\TableNameNotFound
     */
    public function denormalize(array $data): TopwireContext
    {
        $context = new TopwireContext(
            renderingPath: new RenderingPath($data['renderingPath']),
            contextRecord: new ContextRecord(...$data['contextRecord']),
        );
        if (isset($data['attributes'])) {
            foreach ($data['attributes'] as $name => $attributeData) {
                if (isset(self::attributeMap[$name])) {
                    /** @var Attribute $className */
                    $className = self::attributeMap[$name];
                    $attribute = $className::denormalize($attributeData, ['context' => $context]);
                    if ($attribute instanceof Attribute) {
                        $context = $context->withAttribute($name, $attribute);
                    }
                }
            }
        }
        return $context;
    }
}
