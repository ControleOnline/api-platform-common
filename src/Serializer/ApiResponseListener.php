<?php

namespace ControleOnline\Serializer;

use ControleOnline\Service\ExtraDataService;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ExtraDataNormalizer implements NormalizerInterface
{
    public function __construct(
        private NormalizerInterface $decorated,
        private ExtraDataService $extraDataService
    ) {}

    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): bool {
        if (!is_object($data)) {
            return false;
        }

        if (isset($context['__extra_data_done'])) {
            return false;
        }

        if (!method_exists($data, 'getId')) {
            return false;
        }

        return true;
    }

    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {

        $context['__extra_data_done'] = true;

        $normalized = $this->decorated->normalize($data, $format, $context);

        if (!is_array($normalized)) {
            return $normalized;
        }

        $normalized['extra_data'] =['x' =>'y'];
            //$this->extraDataService->getExtraData($data);

        return $normalized;
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => false];
    }
}
