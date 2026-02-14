<?php

namespace ControleOnline\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class ExtraDataNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'extra_data_normalizer';

    public function getSupportedTypes(?string $format): array
    {
        return [
            '*' => false,
            'object' => true,
        ];
    }

    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): bool {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        if (!is_object($data)) {
            return false;
        }

        if (!isset($context['resource_class'])) {
            return false;
        }

        return true;
    }

    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): mixed {
        $context[self::ALREADY_CALLED] = true;

        $data = $this->normalizer->normalize($object, $format, $context);

        if (is_array($data)) {
            $data['extra_data'] = [
                'timestamp' => time(),
                'custom' => 'valor_dinamico',
            ];
        }

        return $data;
    }
}
