<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\People;
use ControleOnline\Service\FileService;
use PHPUnit\Framework\TestCase;

final class FileServiceTest extends TestCase
{
    public function testGetFileUrlUsesLogoForCompanies(): void
    {
        $people = new People();
        $this->setEntityId($people, 10);
        $people->setPeopleType('J');

        $expected = [
            'id' => 91,
            'domain' => 'https://cdn.example.test',
            'url' => '/files/91/download',
        ];

        $service = $this->getMockBuilder(FileService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPeopleMediaFileUrl'])
            ->getMock();
        $service
            ->expects(self::once())
            ->method('getPeopleMediaFileUrl')
            ->with($people, 'logo')
            ->willReturn($expected);

        self::assertSame($expected, $service->getFileUrl($people));
    }

    public function testGetFileUrlUsesAvatarForPeople(): void
    {
        $people = new People();
        $this->setEntityId($people, 11);
        $people->setPeopleType('F');

        $expected = [
            'id' => 92,
            'domain' => 'https://cdn.example.test',
            'url' => '/files/92/download',
        ];

        $service = $this->getMockBuilder(FileService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPeopleMediaFileUrl'])
            ->getMock();
        $service
            ->expects(self::once())
            ->method('getPeopleMediaFileUrl')
            ->with($people, 'avatar')
            ->willReturn($expected);

        self::assertSame($expected, $service->getFileUrl($people));
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
