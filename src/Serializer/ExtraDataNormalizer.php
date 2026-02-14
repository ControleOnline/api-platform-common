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

    private function injectExtraDataRecursive(array $data): array
    {
        // Só adiciona em objetos JSON-LD
        if (isset($data['@id']) && isset($data['@type'])) {
            $data['extra_data'] = ['teste' => 'ok'];
        }

        foreach ($data as $key => $value) {

            if (is_array($value)) {

                // Só percorre se for lista ou objeto JSON
                if (array_is_list($value)) {

                    foreach ($value as $k => $item) {
                        if (is_array($item)) {
                            $value[$k] = $this->injectExtraDataRecursive($item);
                        }
                    }

                    $data[$key] = $value;
                } elseif (isset($value['@id']) || isset($value['id'])) {

                    $data[$key] = $this->injectExtraDataRecursive($value);
                }
            }
        }

        return $data;
    }


    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }
}
