<?php

namespace ControleOnline\Serializer;

use ControleOnline\Service\ExtraDataService;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class ExtraDataNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function __construct(private ExtraDataService $extraDataService) {}

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data)
            && method_exists($data, 'setExtraData')
            && empty($context['_extra_data_applied'][spl_object_id($data)]);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $objectId = spl_object_id($object);
        $context['_extra_data_applied'][$objectId] = true;

        $extraDataEntities = $this->extraDataService->getExtraDataFromEntity($object);

        $extraDataArray = [];
        foreach ($extraDataEntities as $extraData) {
            $extraDataArray[] = [
                'id' => $extraData->getId(),
                'entity_id' => $extraData->getEntityId(),
                'entity_name' => $extraData->getEntityName(),
                'value' => $extraData->getValue(),
                'extra_fields' => [
                    'id' => $extraData->getExtraFields()->getId(),
                    'name' => $extraData->getExtraFields()->getName(),
                    'type' => $extraData->getExtraFields()->getType(),
                    'context' => $extraData->getExtraFields()->getContext(),
                    'configs' => $extraData->getExtraFields()->getConfigs(),
                    'required' => $extraData->getExtraFields()->isRequired()
                ]
            ];
        }

        $object->setExtraData($extraDataArray);

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => false,
        ];
    }
}
