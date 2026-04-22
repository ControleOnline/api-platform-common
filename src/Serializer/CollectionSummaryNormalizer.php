<?php

namespace ControleOnline\Serializer;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ControleOnline\State\CollectionSummaryResult;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CollectionSummaryNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'controleonline_collection_summary_normalizer_called';

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if ($context[self::ALREADY_CALLED] ?? false) {
            return false;
        }

        if ($data instanceof CollectionSummaryResult) {
            return true;
        }

        if ('csv' === $format || !$this->isCollectionOperation($context)) {
            return false;
        }

        return is_iterable($data) || is_array($data);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $summary = null;
        $collection = $object;

        if ($object instanceof CollectionSummaryResult) {
            $summary = $object->getSummary();
            $collection = $object->getCollection();
        }

        $context[self::ALREADY_CALLED] = true;
        $data = $this->normalizer->normalize($collection, $format, $context);

        if (!is_array($data) || 'csv' === $format) {
            return $data;
        }

        $data = $this->normalizeCollectionEnvelope($data);

        if (null !== $summary) {
            $data['summary'] = $summary;
        }

        return $data;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            CollectionSummaryResult::class => true,
            'iterable' => false,
        ];
    }

    private function isCollectionOperation(array $context): bool
    {
        $operation = $context['operation'] ?? $context['api_operation'] ?? null;

        return $operation instanceof CollectionOperationInterface;
    }

    private function normalizeCollectionEnvelope(array $data): array
    {
        if (array_key_exists('hydra:member', $data) && !array_key_exists('member', $data)) {
            $data['member'] = $data['hydra:member'];
        }

        if (array_key_exists('hydra:totalItems', $data) && !array_key_exists('totalItems', $data)) {
            $data['totalItems'] = $data['hydra:totalItems'];
        }

        if (array_key_exists('hydra:search', $data) && !array_key_exists('search', $data)) {
            $data['search'] = $data['hydra:search'];
        }

        if (array_key_exists('hydra:view', $data) && !array_key_exists('view', $data)) {
            $data['view'] = $data['hydra:view'];
        }

        unset(
            $data['hydra:member'],
            $data['hydra:totalItems'],
            $data['hydra:search'],
            $data['hydra:view']
        );

        return $data;
    }
}
