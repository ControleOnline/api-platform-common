<?php

namespace ControleOnline\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use ControleOnline\Entity\File;
use ControleOnline\Service\FileService;
use Doctrine\ORM\QueryBuilder;

/**
 * Applies FileService::securityFilter on File reads (app-community#296).
 */
class FileSecurityExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private readonly FileService $fileService,
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->applySecurityFilter($queryBuilder, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->applySecurityFilter($queryBuilder, $resourceClass);
    }

    private function applySecurityFilter(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if ($resourceClass !== File::class) {
            return;
        }

        $this->fileService->securityFilter(
            $queryBuilder,
            $resourceClass,
            'api_platform',
            $queryBuilder->getRootAliases()[0] ?? null
        );
    }
}
