<?php

namespace ControleOnline\Serializer;

use Symfony\Component\Serializer\SerializerInterface;

class ApiSerializerDecorator implements SerializerInterface
{
    public function __construct(
        private SerializerInterface $decorated
    ) {}

    public function serialize(
        mixed $data,
        string $format,
        array $context = []
    ): string {
        $result = $this->decorated->serialize($data, $format, $context);

        if (
            $format === 'json'
            && isset($context['resource_class'])
        ) {
            $decoded = json_decode($result, true);

            if (is_array($decoded)) {
                $decoded['extra_data'] = [
                    'timestamp' => time(),
                    'custom' => 'valor_dinamico'
                ];
            }

            return json_encode($decoded);
        }

        return $result;
    }

    public function deserialize(
        mixed $data,
        string $type,
        string $format,
        array $context = []
    ): mixed {
        return $this->decorated->deserialize($data, $type, $format, $context);
    }
}
