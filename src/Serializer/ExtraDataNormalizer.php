<?php

namespace ControleOnline\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class ExtraDataNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'extra_data_normalizer_already_called';

    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => true,
        ];
    }

    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): bool {
        return is_object($data) && !isset($context[self::ALREADY_CALLED]);
    }

    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {
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
