<?php

namespace ControleOnline\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ExtraDataNormalizer implements NormalizerInterface
{
    public function __construct(
        private NormalizerInterface $normalizer
    ) {}

    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): bool {
        return is_object($data)
            && !isset($context['__extra_data_added']);
    }

    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {

        $context['__extra_data_added'] = true;

        $normalized = $this->normalizer->normalize($data, $format, $context);

        if (is_array($normalized)) {
            $normalized['extra_data'] = ['teste' => 'ok'];
        }

        return $normalized;
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => false];
    }
}
