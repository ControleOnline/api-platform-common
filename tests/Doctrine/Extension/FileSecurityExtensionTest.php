<?php

namespace ControleOnline\Tests\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ControleOnline\Doctrine\Extension\FileSecurityExtension;
use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use ControleOnline\Service\FileService;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class FileSecurityExtensionTest extends TestCase
{
    public function testAppliesSecurityFilterToFileCollection(): void
    {
        $fileService = $this->createMock(FileService::class);
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder->method('getRootAliases')->willReturn(['file']);
        $queryNameGenerator = $this->createStub(QueryNameGeneratorInterface::class);

        $fileService
            ->expects(self::once())
            ->method('securityFilter')
            ->with($queryBuilder, File::class, 'api_platform', 'file');

        $extension = new FileSecurityExtension($fileService);
        $extension->applyToCollection($queryBuilder, $queryNameGenerator, File::class);
    }

    public function testAppliesSecurityFilterToFileItem(): void
    {
        $fileService = $this->createMock(FileService::class);
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder->method('getRootAliases')->willReturn(['file']);
        $queryNameGenerator = $this->createStub(QueryNameGeneratorInterface::class);

        $fileService
            ->expects(self::once())
            ->method('securityFilter')
            ->with($queryBuilder, File::class, 'api_platform', 'file');

        $extension = new FileSecurityExtension($fileService);
        $extension->applyToItem($queryBuilder, $queryNameGenerator, File::class, ['id' => 1]);
    }

    public function testIgnoresOtherResources(): void
    {
        $fileService = $this->createMock(FileService::class);
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryNameGenerator = $this->createStub(QueryNameGeneratorInterface::class);

        $fileService->expects(self::never())->method('securityFilter');

        $extension = new FileSecurityExtension($fileService);
        $extension->applyToCollection($queryBuilder, $queryNameGenerator, People::class);
    }
}
