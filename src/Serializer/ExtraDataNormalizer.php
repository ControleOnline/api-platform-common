<?php

namespace ControleOnline\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class ExtraDataNormalizer implements
    NormalizerInterface,
    NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): bool {
        return is_object($data)
            && !isset($context['_extra_data_done']);
    }

    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {

        $context['_extra_data_done'] = true;

        $data = $this->normalizer->normalize($object, $format, $context);

        return $this->injectExtraDataRecursive($data);
    }

    private function injectExtraDataRecursive($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        if (isset($data['@id']) || isset($data['id'])) {
            $data['extra_data'] = ['teste' => 'ok'];
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->injectExtraDataRecursive($value);
            }
        }

        return $data;
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }
}
