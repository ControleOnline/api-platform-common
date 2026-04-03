<?php

namespace ControleOnline\State;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider as DoctrineCollectionProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ControleOnline\Service\CollectionSummaryService;

class CollectionSummaryProvider implements ProviderInterface
{
    public function __construct(
        private ProviderInterface $inner,
        private CollectionSummaryService $collectionSummaryService
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $result = $this->inner->provide($operation, $uriVariables, $context);

        if (
            isset($context['graphql_operation_name'])
            || !$operation instanceof CollectionOperationInterface
            || !in_array($operation->getProvider(), [DoctrineCollectionProvider::class, 'api_platform.doctrine.orm.state.collection_provider'], true)
        ) {
            return $result;
        }

        $summary = $this->collectionSummaryService->buildSummary($operation, $uriVariables, $context);
        if (null === $summary) {
            return $result;
        }

        return new CollectionSummaryResult($result, $summary);
    }
}
