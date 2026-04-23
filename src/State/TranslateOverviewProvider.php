<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ControleOnline\Entity\TranslateOverview;
use ControleOnline\Service\TranslateService;

class TranslateOverviewProvider implements ProviderInterface
{
    public function __construct(private TranslateService $translateService) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $overview = $this->translateService->buildOverview($context['filters'] ?? []);

        // The collection wrapper keeps the response on the module standard envelope with `member` and `summary`.
        return new CollectionSummaryResult(
            array_map(
                static fn (array $item) => TranslateOverview::fromArray($item),
                $overview['items']
            ),
            $overview['summary']
        );
    }
}
