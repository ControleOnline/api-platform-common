<?php

namespace ControleOnline\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ApiExtraDataNormalizer implements NormalizerInterface
{
    public function __construct(
        private NormalizerInterface $decorated
    ) {}

    public function supportsNormalization(
        mixed $data,
        string $format = null,
        array $context = []
    ): bool {
        return isset($context['resource_class']);
    }

    public function normalize(
        mixed $data,
        string $format = null,
        array $context = []
    ) {
        $normalized = $this->decorated->normalize($data, $format, $context);

        if (is_array($normalized)) {
            $normalized['extra_data'] = [
                'timestamp' => time(),
                'custom' => 'valor_dinamico'
            ];
        }

        return $normalized;
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['*' => false];
    }
}
