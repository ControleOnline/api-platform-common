<?php

namespace ControleOnline\Serializer;

use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;

class ApiExtraDataNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function supportsNormalization(
        mixed $data,
        string $format = null,
        array $context = []
    ): bool {
        return isset($context['resource_class']);
    }

    public function normalize(
        mixed $object,
        string $format = null,
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
}
