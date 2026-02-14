<?php

namespace ControleOnline\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

class ExtraDataNormalizer implements
    ContextAwareNormalizerInterface,
    NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function supportsNormalization(
        $data,
        string $format = null,
        array $context = []
    ): bool {
        return is_object($data)
            && !isset($context['_extra_data_done']);
    }

    public function normalize(
        $object,
        string $format = null,
        array $context = []
    ) {
        $context['_extra_data_done'] = true;

        $data = $this->normalizer->normalize($object, $format, $context);

        return $this->injectExtraDataRecursive($data);
    }

    private function injectExtraDataRecursive($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        // Se for objeto normalizado (tem @id ou id)
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
        return ['object' => false];
    }
}
