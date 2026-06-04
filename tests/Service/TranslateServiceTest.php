<?php

namespace ControleOnline\Common\Tests\Service;

use ControleOnline\Entity\Language;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Translate;
use ControleOnline\Entity\User;
use ControleOnline\Repository\LanguageRepository;
use ControleOnline\Repository\PeopleRepository;
use ControleOnline\Repository\TranslateRepository;
use ControleOnline\Service\PeopleRoleService;
use ControleOnline\Service\TranslateService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TranslateServiceTest extends TestCase
{
    public function testPersistFromPayloadCreatesARevisedTranslation(): void
    {
        [$service, $manager, $existingTranslation] = $this->buildService();
        $persistedTranslation = null;

        $manager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function ($entity) use (&$persistedTranslation) {
                self::assertInstanceOf(Translate::class, $entity);
                $persistedTranslation = $entity;

                return true;
            }));
        $manager
            ->expects(self::once())
            ->method('flush');

        $result = $service->persistFromPayload([
            'key' => 'orders',
            'language' => '/languages/1',
            'people' => '/people/1',
            'store' => 'menu',
            'type' => 'label',
            'translate' => 'Pedidos',
            'revised' => true,
        ]);

        self::assertSame($persistedTranslation, $result);
        self::assertInstanceOf(Translate::class, $result);
        self::assertSame('orders', $result->getKey());
        self::assertSame('menu', $result->getStore());
        self::assertSame('label', $result->getType());
        self::assertSame('Pedidos', $result->getTranslate());
        self::assertTrue($result->isRevised());
        self::assertNull($existingTranslation);
    }

    public function testPersistFromPayloadCreatesANonRevisedTranslation(): void
    {
        [$service, $manager, $existingTranslation] = $this->buildService();
        $persistedTranslation = null;

        $manager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function ($entity) use (&$persistedTranslation) {
                self::assertInstanceOf(Translate::class, $entity);
                $persistedTranslation = $entity;

                return true;
            }));
        $manager
            ->expects(self::once())
            ->method('flush');

        $result = $service->persistFromPayload([
            'key' => 'accountsReceivable',
            'language' => '/languages/1',
            'people' => '/people/1',
            'store' => 'invoice',
            'type' => 'label',
            'translate' => 'accountsReceivable',
            'revised' => false,
        ]);

        self::assertSame($persistedTranslation, $result);
        self::assertInstanceOf(Translate::class, $result);
        self::assertSame('accountsReceivable', $result->getKey());
        self::assertSame('invoice', $result->getStore());
        self::assertSame('label', $result->getType());
        self::assertSame('accountsReceivable', $result->getTranslate());
        self::assertFalse($result->isRevised());
        self::assertNull($existingTranslation);
    }

    public function testPersistFromPayloadDoesNotUpdateExistingNonRevisedTranslation(): void
    {
        $existingTranslation = new Translate();
        $existingTranslation->setKey('orders');
        $existingTranslation->setStore('menu');
        $existingTranslation->setType('label');
        $existingTranslation->setTranslate('Pedidos antigos');
        $existingTranslation->setRevised(false);
        $this->setEntityId($existingTranslation, 27);

        [$service, $manager] = $this->buildService($existingTranslation);

        $manager
            ->expects(self::never())
            ->method('persist');
        $manager
            ->expects(self::never())
            ->method('flush');

        $result = $service->persistFromPayload([
            'key' => 'orders',
            'language' => '/languages/1',
            'people' => '/people/1',
            'store' => 'menu',
            'type' => 'label',
            'translate' => 'Pedidos novos',
            'revised' => false,
        ]);

        self::assertSame($existingTranslation, $result);
        self::assertSame('Pedidos antigos', $result->getTranslate());
        self::assertFalse($result->isRevised());
        self::assertSame(27, $result->getId());
    }

    public function testPersistFromPayloadUpdatesExistingRevisedTranslation(): void
    {
        $existingTranslation = new Translate();
        $existingTranslation->setKey('orders');
        $existingTranslation->setStore('menu');
        $existingTranslation->setType('label');
        $existingTranslation->setTranslate('Pedidos antigos');
        $existingTranslation->setRevised(false);
        $this->setEntityId($existingTranslation, 27);

        [$service, $manager] = $this->buildService($existingTranslation);

        $manager
            ->expects(self::never())
            ->method('persist');
        $manager
            ->expects(self::once())
            ->method('flush');

        $result = $service->persistFromPayload([
            'key' => 'orders',
            'language' => '/languages/1',
            'people' => '/people/1',
            'store' => 'menu',
            'type' => 'label',
            'translate' => 'Pedidos novos',
            'revised' => true,
        ]);

        self::assertSame($existingTranslation, $result);
        self::assertSame('Pedidos novos', $result->getTranslate());
        self::assertTrue($result->isRevised());
        self::assertSame(27, $result->getId());
    }

    /**
     * @return array{0: TranslateService, 1: EntityManagerInterface, 2: ?Translate}
     */
    private function buildService(?Translate $existingTranslation = null): array
    {
        $company = new People();
        $company->setName('Empresa 1');
        $company->setAlias('Empresa 1');
        $company->setPeopleType('J');
        $this->setEntityId($company, 1);

        $language = new Language();
        $language->setLanguage('pt-br');
        $language->setLocked(false);
        $this->setEntityId($language, 1);

        $user = new User();
        $user->setPeople($company);
        $user->setUsername('tester@example.com');

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($user);

        $security = $this->createMock(TokenStorageInterface::class);
        $security
            ->method('getToken')
            ->willReturn($token);

        $peopleRepository = $this->createMock(PeopleRepository::class);
        $peopleRepository
            ->method('find')
            ->with('1')
            ->willReturn($company);

        $languageRepository = $this->createMock(LanguageRepository::class);
        $languageRepository
            ->method('find')
            ->with('1')
            ->willReturn($language);

        if ($existingTranslation instanceof Translate) {
            $translateRepository = $this->createMock(TranslateRepository::class);
            $translateRepository
                ->method('findOneBy')
                ->willReturn($existingTranslation);
        } else {
            $translateRepository = $this->createMock(TranslateRepository::class);
            $translateRepository
                ->method('findOneBy')
                ->willReturn(null);
        }

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->method('getRepository')
            ->willReturnCallback(static function (string $class) use (
                $peopleRepository,
                $languageRepository,
                $translateRepository
            ) {
                return match ($class) {
                    People::class => $peopleRepository,
                    Language::class => $languageRepository,
                    Translate::class => $translateRepository,
                    default => throw new \RuntimeException('Unexpected repository: ' . $class),
                };
            });

        $peopleRoleService = $this->createMock(PeopleRoleService::class);

        return [
            new TranslateService($manager, $security, $peopleRoleService),
            $manager,
            $existingTranslation,
        ];
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        while (!$reflection->hasProperty('id') && ($reflection = $reflection->getParentClass())) {
            // Walk up the inheritance tree until the id field is found.
        }

        if (!$reflection instanceof \ReflectionClass || !$reflection->hasProperty('id')) {
            throw new \RuntimeException('Unable to set entity id for ' . $entity::class);
        }

        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
