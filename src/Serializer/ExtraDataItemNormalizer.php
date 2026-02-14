<?php

namespace ControleOnline\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ExtraDataItemNormalizer implements NormalizerInterface
{
    public function __construct(private NormalizerInterface $decorated)
    {
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->decorated->getSupportedTypes($format);
    }

    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {
        $data = $this->decorated->normalize($object, $format, $context);

        if (is_array($data)) {
            $data['extra_data'] = [
                'timestamp' => time(),
                'custom' => 'valor_dinamico',
            ];
        }

        return $data;
    }
}
