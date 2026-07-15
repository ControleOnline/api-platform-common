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

    public function testPersistFromPayloadCreatesANonRevisedRuntimeTranslation(): void
    {
        [$service, $manager, $existingTranslation] = $this->buildService();

        $manager
            ->expects(self::once())
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
            'translate' => 'Orders',
            'revised' => false,
        ]);

        self::assertInstanceOf(Translate::class, $result);
        self::assertSame('Orders', $result->getTranslate());
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

    public function testPersistFromPayloadKeepsExistingTranslationWhenRuntimePayloadIsNotRevised(): void
    {
        $existingTranslation = new Translate();
        $existingTranslation->setKey('orders');
        $existingTranslation->setStore('menu');
        $existingTranslation->setType('label');
        $existingTranslation->setTranslate('Pedidos revisados');
        $existingTranslation->setRevised(true);
        $this->setEntityId($existingTranslation, 55);

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
            'translate' => 'Orders auto generated',
            'revised' => false,
        ]);

        self::assertSame($existingTranslation, $result);
        self::assertSame('Pedidos revisados', $result->getTranslate());
        self::assertTrue($result->isRevised());
        self::assertSame(55, $result->getId());
    }

    public function testResolveFromPayloadUsesCompanyFallbackAndCreatesMissingDefaultTranslation(): void
    {
        $selectedCompany = new People();
        $selectedCompany->setName('Empresa selecionada');
        $selectedCompany->setAlias('Empresa selecionada');
        $selectedCompany->setPeopleType('J');
        $this->setEntityId($selectedCompany, 5);

        $mainCompany = new People();
        $mainCompany->setName('Empresa principal');
        $mainCompany->setAlias('Empresa principal');
        $mainCompany->setPeopleType('J');
        $this->setEntityId($mainCompany, 1);

        $language = new Language();
        $language->setLanguage('pt-br');
        $language->setLocked(false);
        $this->setEntityId($language, 1);

        $user = new User();
        $user->setPeople($selectedCompany);
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
            ->willReturnCallback(static function (string $id) use ($selectedCompany, $mainCompany) {
                return match ($id) {
                    '5' => $selectedCompany,
                    '1' => $mainCompany,
                    default => null,
                };
            });
        $peopleRepository
            ->method('getCompanyPeopleLinks')
            ->willReturn(new \stdClass());

        $languageRepository = $this->createMock(LanguageRepository::class);
        $languageRepository
            ->method('find')
            ->with('1')
            ->willReturn($language);

        $companySaveTranslation = new Translate();
        $companySaveTranslation->setPeople($selectedCompany);
        $companySaveTranslation->setLanguage($language);
        $companySaveTranslation->setStore('menu');
        $companySaveTranslation->setType('label');
        $companySaveTranslation->setKey('save');
        $companySaveTranslation->setTranslate('Salvar empresa');
        $companySaveTranslation->setRevised(true);
        $this->setEntityId($companySaveTranslation, 11);

        $mainCancelTranslation = new Translate();
        $mainCancelTranslation->setPeople($mainCompany);
        $mainCancelTranslation->setLanguage($language);
        $mainCancelTranslation->setStore('menu');
        $mainCancelTranslation->setType('label');
        $mainCancelTranslation->setKey('cancel');
        $mainCancelTranslation->setTranslate('Cancelar');
        $mainCancelTranslation->setRevised(true);
        $this->setEntityId($mainCancelTranslation, 22);

        $translateRepository = $this->createMock(TranslateRepository::class);
        $translateRepository
            ->method('findForOverview')
            ->willReturnCallback(static function (
                People $people,
                Language $requestedLanguage,
                array $filters
            ) use ($selectedCompany, $mainCompany, $companySaveTranslation, $mainCancelTranslation): array {
                self::assertSame('pt-br', $requestedLanguage->getLanguage());
                self::assertSame('menu', $filters['store']);
                self::assertSame('label', $filters['type']);
                self::assertSame(['save', 'cancel', 'delete'], $filters['keys']);

                return match ($people->getId()) {
                    5 => [$companySaveTranslation],
                    1 => [$mainCancelTranslation],
                    default => [],
                };
            });
        $translateRepository
            ->method('findOneBy')
            ->willReturn(null);

        $persistedTranslations = [];
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
        $manager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function ($entity) use (&$persistedTranslations) {
                self::assertInstanceOf(Translate::class, $entity);
                $persistedTranslations[] = $entity;

                return true;
            }));
        $manager
            ->expects(self::once())
            ->method('flush');

        $peopleRoleService = $this->createMock(PeopleRoleService::class);
        $peopleRoleService
            ->method('getMainCompany')
            ->willReturn($mainCompany);

        $service = new TranslateService($manager, $security, $peopleRoleService);

        $result = $service->resolveFromPayload([
            'people' => '/people/5',
            'language' => '/languages/1',
            'requests' => [
                [
                    'store' => 'menu',
                    'type' => 'label',
                    'keys' => ['save', 'cancel', 'delete'],
                ],
            ],
        ]);

        self::assertCount(3, $result);
        self::assertSame('save', $result[0]['key']);
        self::assertSame('Salvar empresa', $result[0]['translate']);
        self::assertSame('company', $result[0]['source']);
        self::assertSame('cancel', $result[1]['key']);
        self::assertSame('Cancelar', $result[1]['translate']);
        self::assertSame('main_company', $result[1]['source']);
        self::assertSame('delete', $result[2]['key']);
        self::assertSame('Delete', $result[2]['translate']);
        self::assertSame('main_company', $result[2]['source']);
        self::assertCount(1, $persistedTranslations);
        self::assertSame('delete', $persistedTranslations[0]->getKey());
        self::assertSame('Delete', $persistedTranslations[0]->getTranslate());
        self::assertFalse($persistedTranslations[0]->isRevised());
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
