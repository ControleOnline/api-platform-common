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
            && method_exists($data, 'setExtraData')
            && !isset($context['_extra_data_done']);
    }

    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {

        $context['_extra_data_done'] = true;

        // Aqui você pode buscar no banco usando:
        // $object->getId()
        // get_class($object)

        $object->setExtraData([
            'teste' => 'ok'
        ]);

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => false];
    }
}
