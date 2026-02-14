<?php

namespace ControleOnline\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class ApiExtraDataNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): bool {
        return isset($context['resource_class']);
    }

    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): mixed {
        $data = $this->normalizer->normalize($object, $format, $context);

        if (is_array($data)) {
            $data['extra_data'] = [
                'timestamp' => time(),
                'custom' => 'valor_dinamico'
            ];
        }

        return $data;
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['*' => false];
    }
}
