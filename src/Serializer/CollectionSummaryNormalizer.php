<?php

namespace ControleOnline\Serializer;

use ControleOnline\State\CollectionSummaryResult;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CollectionSummaryNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof CollectionSummaryResult;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $data = $this->normalizer->normalize($object->getCollection(), $format, $context);

        if (!is_array($data) || 'csv' === $format) {
            return $data;
        }

        $data['summary'] = $object->getSummary();

        return $data;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            CollectionSummaryResult::class => true,
        ];
    }
}
