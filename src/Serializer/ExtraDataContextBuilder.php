<?php

namespace ControleOnline\Serializer;

use ApiPlatform\State\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

class ExtraDataContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(
        private SerializerContextBuilderInterface $decorated
    ) {
    }

    public function createFromRequest(
        Request $request,
        bool $normalization,
        ?array $attributes = null
    ): array {
        $context = $this->decorated->createFromRequest(
            $request,
            $normalization,
            $attributes
        );

        if ($normalization) {
            $context['extra_data'] = [
                'timestamp' => time(),
                'custom' => 'valor_dinamico',
            ];
        }

        return $context;
    }
}
