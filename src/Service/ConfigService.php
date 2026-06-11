<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\Device;
use ControleOnline\Entity\Module;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;


class ConfigService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private WalletService $walletService,
        private PeopleService $peopleService,
        private TechnicalConfigAccessService $technicalConfigAccessService,
        private ?DomainService $domainService = null,
    ) {}

    public function getConfig(?People $people, $key, $json = false)
    {
        if (!$people instanceof People) {
            return null;
        }

        $config = $this->discoveryConfig($people, $key, false);
        $value =  $config ? $config->getConfigValue() : null;
        return $json ? json_decode($value, true) : $value;
    }

    public function discoveryConfig(People $people, $key, $create = true): ?Config
    {
        $config =   $this->manager->getRepository(Config::class)->findOneBy([
            'people' => $people,
            'configKey' => $key
        ]);
        if ($config)
            return $config;
        if ($create) {
            $config = new Config();
            $config->setConfigKey($key);
            $config->setPeople($people);

            return $config;
        }
        return null;
    }

    public function addConfig(
        People $people,
        string $key,
        $values,
        Module $module,
        ?string $visibility = 'private'
    ) {
        $this->technicalConfigAccessService->assertCanManageConfig($people, $key);

        $configs = $this->manager->getRepository(Config::class)->findBy([
            'people' => $people,
            'configKey' => $key
        ]);

        if (empty($configs)) {
            $config = new Config();
            $config->setConfigKey($key);
            $config->setPeople($people);
            $configs = [$config];
        }

        $shouldUpdateModule = count($configs) === 1;

        foreach ($configs as $config) {
            $this->applyConfigValue($config, $values);
            $config->setVisibility($visibility);

            if ($shouldUpdateModule) {
                $config->setModule($module);
            }

            $this->manager->persist($config);
        }

        $this->manager->flush();
        return $configs[0];
    }

    private function applyConfigValue(Config $config, mixed $values): void
    {
        if (is_array($values)) {
            $isList = $values === [] || array_keys($values) === range(0, count($values) - 1);
            if ($isList) {
                $config->setConfigValue(json_encode($values));
                return;
            }

            $currentValue = json_decode($config->getConfigValue(), true);
            $newValue = is_array($currentValue) ? $currentValue : [];
            foreach ($values as $k => $v) {
                $newValue[$k] = $v;
            }
            $config->setConfigValue(json_encode($newValue));
            return;
        }

        $config->setConfigValue($values);
    }

    public function addConfigFromPayload(array $payload): Config
    {
        return $this->addConfig(
            $this->resolvePeopleReference($payload['people'] ?? ''),
            $payload['configKey'],
            $this->decodeConfigValue($payload['configValue'] ?? null),
            $this->resolveModuleReference($payload['module'] ?? ''),
            $payload['visibility'] ?? 'public'
        );
    }

    public function addConfigFromJson(?string $content): Config
    {
        return $this->addConfigFromPayload($this->decodePayload($content));
    }

    public function addConfigsFromPayload(array $payload): array
    {
        $people = $this->resolvePeopleReference($payload['people'] ?? '');
        $module = $this->resolveModuleReference($payload['module'] ?? '');
        $visibility = $payload['visibility'] ?? 'public';
        $configs = is_array($payload['configs'] ?? null) ? $payload['configs'] : [];
        $savedItems = [];

        foreach ($configs as $configItem) {
            $configKey = $configItem['configKey'] ?? null;

            if (!$configKey) {
                continue;
            }

            $savedItems[] = $this->addConfig(
                $people,
                $configKey,
                $this->normalizeConfigValue($configItem['configValue'] ?? ''),
                $module,
                $visibility
            );
        }

        return $savedItems;
    }

    public function addConfigsFromJson(?string $content): array
    {
        return $this->addConfigsFromPayload($this->decodePayload($content));
    }

    public function normalizeConfigValue(mixed $configValue): mixed
    {
        if (!is_string($configValue)) {
            return $configValue;
        }

        if (trim($configValue) === '') {
            return '';
        }

        try {
            return json_decode($configValue, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $configValue;
        }
    }

    public function resolvePeopleReference(mixed $peopleReference): ?People
    {
        return $this->manager->getRepository(People::class)->find(
            $this->normalizeReferenceId($peopleReference)
        );
    }

    public function resolveModuleReference(mixed $moduleReference): ?Module
    {
        return $this->manager->getRepository(Module::class)->find(
            $this->normalizeReferenceId($moduleReference)
        );
    }

    public function resolveDeviceReference(mixed $deviceReference): ?Device
    {
        $normalizedDevice = trim((string) $deviceReference);
        if ($normalizedDevice === '') {
            return null;
        }

        return $this->manager->getRepository(Device::class)->findOneBy([
            'device' => $normalizedDevice,
        ]);
    }

    public function decodeConfigValue(mixed $configValue): mixed
    {
        return json_decode($configValue, true);
    }

    public function decodePayload(?string $content): array
    {
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeReferenceId(mixed $reference): string
    {
        return preg_replace("/[^0-9]/", "", (string) $reference);
    }

    public function discoveryMainConfigs(People $people, ?Device $device = null)
    {
        $this->discoveryCashWallet($people);
        $this->discoveryWithdrawlWallet($people);
        $this->discoveryInfinitePayWallet($people);
        $this->discoveryCieloWallet($people);

        return $this->getCompanyConfigs($people);
    }

    public function discoveryMainConfigsFromPayload(
        array $payload,
        ?string $deviceIdentifier = null
    )
    {
        $people = $this->resolvePeopleReference($payload['people'] ?? '');
        if (!$people instanceof People) {
            throw new \InvalidArgumentException('People not found');
        }

        return $this->discoveryMainConfigs(
            $people,
            $this->resolveDeviceReference($deviceIdentifier)
        );
    }

    public function discoveryMainConfigsFromJson(
        ?string $content,
        ?string $deviceIdentifier = null
    )
    {
        return $this->discoveryMainConfigsFromPayload(
            $this->decodePayload($content),
            $deviceIdentifier
        );
    }

    public function getCompanyConfigs(People $people, $visibility = 'public')
    {
        return   $this->manager->getRepository(Config::class)->findBy([
            'people' => $people,
            'visibility' => $visibility
        ]);
    }

    public function securityFilter(
        QueryBuilder $queryBuilder,
        $resourceClass = null,
        $applyTo = null,
        $rootAlias = null
    ): void {
        $rootAlias ??= $queryBuilder->getRootAliases()[0] ?? null;
        if (!$rootAlias) {
            return;
        }

        $accessiblePeopleIds = [];
        foreach ($this->peopleService->getMyCompanies() as $company) {
            $accessiblePeopleIds[] = (int) $company->getId();
        }

        $myPeople = $this->peopleService->getMyPeople();
        if ($myPeople) {
            $accessiblePeopleIds[] = (int) $myPeople->getId();
        }

        $accessiblePeopleIds = array_values(array_unique(array_filter(
            $accessiblePeopleIds,
            fn(mixed $value): bool => (int) $value > 0
        )));

        if (!$accessiblePeopleIds) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $queryBuilder->andWhere(
            sprintf('IDENTITY(%s.people) IN (:config_people_ids)', $rootAlias)
        );
        $queryBuilder->setParameter('config_people_ids', $accessiblePeopleIds);

        $technicalKeys = $this->technicalConfigAccessService->getTechnicalConfigKeys();
        if (!$technicalKeys) {
            return;
        }

        $mainCompanyId = $this->technicalConfigAccessService->getMainCompanyId();
        if (
            $mainCompanyId !== null
            && $this->technicalConfigAccessService->canAccessMainCompanyTechnicalSettings()
        ) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    sprintf('%s.configKey NOT IN (:technical_config_keys)', $rootAlias),
                    $queryBuilder->expr()->andX(
                        sprintf('%s.configKey IN (:technical_config_keys)', $rootAlias),
                        sprintf('IDENTITY(%s.people) = :technical_main_company_id', $rootAlias)
                    )
                )
            );
            $queryBuilder->setParameter('technical_main_company_id', $mainCompanyId);
        } else {
            $queryBuilder->andWhere(
                sprintf('%s.configKey NOT IN (:technical_config_keys)', $rootAlias)
            );
        }

        $queryBuilder->setParameter('technical_config_keys', $technicalKeys);
    }

    private function discoveryCashWallet(People $company)
    {

        $paymentTypes = [[
            'paymentType' => 'Dinheiro',
            'frequency' => 'single',
            'installments' => 'single',
            'paymentCode' => null
        ]];
        /**
         * @todo Module need be variable
         */
        $module = $this->manager->getRepository(Module::class)->find(8);
        $wallet = $this->walletService->discoverWallet($company, 'Caixa');
        $this->addConfig(
            $company,
            'pos-cash-wallet',
            $wallet->getId(),
            $module,
            'public'
        );

        foreach ($paymentTypes as $paymentType)
            $this->walletService->discoverWalletPaymentType(
                $wallet,
                $this->walletService->discoverPaymentType($company, $paymentType),
                $paymentType['paymentCode']
            );
    }

    private function discoveryWithdrawlWallet(People $company)
    {

        $paymentTypes = [[
            'paymentType' => 'Dinheiro',
            'frequency' => 'single',
            'installments' => 'single',
            'paymentCode' => null
        ]];

        $this->walletService->discoverWallet($company, 'Reserva');
        /**
         * @todo Module need be variable
         */
        $module = $this->manager->getRepository(Module::class)->find(8);
        $wallet = $this->walletService->discoverWallet($company, 'Reserva');
        $this->addConfig(
            $company,
            'pos-withdrawl-wallet',
            $wallet->getId(),
            $module,
            'public'
        );
        foreach ($paymentTypes as $paymentType)
            $this->walletService->discoverWalletPaymentType(
                $wallet,
                $this->walletService->discoverPaymentType($company, $paymentType),
                $paymentType['paymentCode']
            );
    }

    private function discoveryInfinitePayWallet(People $company)
    {
        $paymentTypes =  [
            [
                'paymentType' => 'Débito',
                'frequency' => 'single',
                'installments' => 'single',
                'paymentCode' => 'debit',
            ],
            [
                'paymentType' => 'Crédito à Vista',
                'frequency' => 'single',
                'installments' => 'single',
                'paymentCode' => 'credit',
            ],
            [
                'paymentType' => 'Crédito Parcelado',
                'frequency' => 'single',
                'installments' => 'split',
                'paymentCode' => 'credit',
            ],
        ];


        /**
         * @todo Module need be variable
         */
        $module = $this->manager->getRepository(Module::class)->find(8);
        $wallet = $this->walletService->discoverWallet($company, 'Infine Pay');
        $this->addConfig(
            $company,
            'pos-infinite-pay-wallet',
            $wallet->getId(),
            $module,
            'public'
        );

        foreach ($paymentTypes as $paymentType)
            $this->walletService->discoverWalletPaymentType(
                $wallet,
                $this->walletService->discoverPaymentType($company, $paymentType),
                $paymentType['paymentCode']
            );
    }

    private function discoveryCieloWallet(People $company)
    {
        $paymentTypes = $this->defaultPaymentTypes();

        try {
            $domain = strtolower((string) $this->domainService?->getDomain());
            if (!$domain || !str_contains($domain, 'lave-go.com')) {
                $paymentTypes = array_merge(
                    $paymentTypes,
                    $this->extraPaymentTypes()
                );
            }
        } catch (\Throwable) {
            $paymentTypes = array_merge(
                $paymentTypes,
                $this->extraPaymentTypes()
            );
        }

        /**
         * @todo Module need be variable
         */
        $module = $this->manager->getRepository(Module::class)->find(8);
        $wallet = $this->walletService->discoverWallet($company, 'Cielo');
        $this->addConfig(
            $company,
            'pos-cielo-wallet',
            $wallet->getId(),
            $module,
            'public'
        );

        foreach ($paymentTypes as $paymentType)
            $this->walletService->discoverWalletPaymentType(
                $wallet,
                $this->walletService->discoverPaymentType($company, $paymentType),
                $paymentType['paymentCode']
            );
    }

    private function defaultPaymentTypes(): array
    {
        return [
            [
                'paymentType' => 'Débito',
                'frequency' => 'single',
                'installments' => 'single',

                'paymentCode' => 'DEBITO_AVISTA',
            ],
            [
                'paymentType' => 'Crédito à Vista',
                'frequency' => 'single',
                'installments' => 'single',

                'paymentCode' => 'CREDITO_AVISTA',
            ],
            [
                'paymentType' => 'PIX',
                'frequency' => 'single',
                'installments' => 'single',

                'paymentCode' => 'PIX',
            ],
        ];
    }

    private function extraPaymentTypes(): array
    {
        return [
            [
                'paymentType' => 'Refeição',
                'frequency' => 'single',
                'installments' => 'single',

                'paymentCode' => 'VOUCHER_REFEICAO',
            ],
            [
                'paymentType' => 'Alimentação',
                'frequency' => 'single',
                'installments' => 'single',

                'paymentCode' => 'VOUCHER_ALIMENTACAO',
            ],
            [
                'paymentType' => 'Crédito Parcelado - Cliente',
                'frequency' => 'single',
                'installments' => 'split',

                'paymentCode' => 'CREDITO_PARCELADO_CLIENTE',
            ],
            [
                'paymentType' => 'Crédito Parcelado - Loja',
                'frequency' => 'single',
                'installments' => 'split',

                'paymentCode' => 'CREDITO_PARCELADO_LOJA',
            ],
        ];
    }
}
