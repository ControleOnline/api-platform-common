<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Device;
use ControleOnline\Entity\DeviceConfig;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DeviceService
{
    private const DEFAULT_DEVICE_CONFIG_TYPE = 'DEVICE';
    private const PRINT_DEVICE_TYPE = 'PRINT';
    private const PRINTER_DEVICE_TYPE = 'PRINTER';

    private $request;

    public function __construct(
        private EntityManagerInterface $manager,
        private ConfigService $configService,
        private PeopleService $peopleService,
        RequestStack $requestStack,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getPrinters(People $people)
    {
        $device_configs = $this->manager->getRepository(DeviceConfig::class)->findBy([
            'people' => $people
        ]);
        $devices = [];
        $seenDevices = [];
        foreach ($device_configs as $device_config) {
            if (!$this->isPrinterDeviceConfigType($device_config->getType())) {
                continue;
            }

            $device = $device_config->getDevice();
            $deviceId = $device->getId();
            if (isset($seenDevices[$deviceId])) {
                continue;
            }

            $seenDevices[$deviceId] = true;
            $devices[] = $device;
        }
        return $devices;
    }

    public function findDevices(array|string $devices)
    {
        return $this->manager->getRepository(Device::class)->findBy([
            'device' => $devices
        ]);
    }

    public function discoveryDevice($deviceId)
    {
        $device = $this->manager->getRepository(Device::class)->findOneBy([
            'device' => $deviceId
        ]);
        if (!$device) {
            $device = new Device();
            $device->setDevice($deviceId);
            $this->manager->persist($device);
            $this->manager->flush();
        }
        return $device;
    }

    public function normalizeDeviceConfigType(?string $type): string
    {
        $normalizedType = strtoupper(trim((string) $type));
        return $normalizedType !== ''
            ? $normalizedType
            : self::DEFAULT_DEVICE_CONFIG_TYPE;
    }

    public function resolveDeviceIdentifier(Request $request, array $payload = []): string
    {
        $deviceHeader = trim((string) $request->headers->get('device', ''));
        $deviceBody = trim((string) ($payload['device'] ?? ''));

        return $deviceBody !== '' ? $deviceBody : $deviceHeader;
    }

    public function normalizeDeviceConfigsPayload(mixed $rawConfigs): array
    {
        if (is_array($rawConfigs)) {
            return $rawConfigs;
        }

        if (!is_string($rawConfigs)) {
            return [];
        }

        $decodedConfigs = json_decode($rawConfigs, true);

        return is_array($decodedConfigs) ? $decodedConfigs : [];
    }

    public function resolveDeviceConfigTypeFromRequest(Request $request, array $payload = []): string
    {
        $type = trim((string) (
            $payload['type']
            ?? $request->headers->get('device-type')
            ?? $request->headers->get('type')
            ?? ''
        ));

        return $this->normalizeDeviceConfigType($type);
    }

    private function isPrinterDeviceConfigType(?string $type): bool
    {
        return in_array(
            $this->normalizeDeviceConfigType($type),
            [self::PRINT_DEVICE_TYPE, self::PRINTER_DEVICE_TYPE],
            true
        );
    }

    private function resolveRequestDeviceConfigType(): ?string
    {
        if (!$this->request) {
            return null;
        }

        $payload = [];
        $content = trim((string) $this->request->getContent());
        if ($content !== '') {
            $decodedContent = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedContent)) {
                $payload = $decodedContent;
            }
        }

        $candidates = [
            $payload['type'] ?? null,
            $this->request->request->get('type'),
            $this->request->query->get('type'),
            $this->request->headers->get('device-type'),
            $this->request->headers->get('type'),
        ];

        foreach ($candidates as $candidate) {
            $normalizedType = strtoupper(trim((string) $candidate));
            if ($normalizedType !== '') {
                return $normalizedType;
            }
        }

        return null;
    }

    public function findDeviceConfigs(Device $device, People $people): array
    {
        return $this->manager->getRepository(DeviceConfig::class)->findBy([
            'device' => $device,
            'people' => $people,
        ], ['id' => 'ASC']);
    }

    public function extractDeviceConfigReferenceId(mixed $reference): ?int
    {
        $normalizedReference = trim((string) $reference);
        if ($normalizedReference === '') {
            return null;
        }

        if (preg_match('#/device_configs/(\d+)$#', $normalizedReference, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    public function isDeviceConfigReference(mixed $reference): bool
    {
        return $this->extractDeviceConfigReferenceId($reference) !== null;
    }

    public function findDeviceConfigByReference(
        mixed $reference,
        ?People $people = null
    ): ?DeviceConfig {
        $deviceConfigId = $this->extractDeviceConfigReferenceId($reference);
        if ($deviceConfigId === null) {
            return null;
        }

        $criteria = ['id' => $deviceConfigId];
        if ($people instanceof People) {
            $criteria['people'] = $people;
        }

        return $this->manager->getRepository(DeviceConfig::class)->findOneBy($criteria);
    }

    public function findDeviceConfig(
        Device $device,
        People $people,
        ?string $type = null
    ): ?DeviceConfig {
        $resolvedType = trim((string) $type) !== ''
            ? $this->normalizeDeviceConfigType($type)
            : null;

        if ($resolvedType !== null) {
            return $this->manager->getRepository(DeviceConfig::class)->findOneBy([
                'device' => $device,
                'people' => $people,
                'type' => $resolvedType,
            ]);
        }

        $requestType = $this->resolveRequestDeviceConfigType();
        if ($requestType !== null) {
            $typedDeviceConfig = $this->manager->getRepository(DeviceConfig::class)->findOneBy([
                'device' => $device,
                'people' => $people,
                'type' => $requestType,
            ]);

            if ($typedDeviceConfig instanceof DeviceConfig) {
                return $typedDeviceConfig;
            }
        }

        $deviceConfigs = $this->findDeviceConfigs($device, $people);
        if (count($deviceConfigs) === 1) {
            return $deviceConfigs[0];
        }

        return $deviceConfigs[0] ?? null;
    }

    public function resolveDeviceConfigType(
        Device $device,
        People $people,
        ?string $type = null
    ): string {
        if (trim((string) $type) !== '') {
            return $this->normalizeDeviceConfigType($type);
        }

        $requestType = $this->resolveRequestDeviceConfigType();
        if ($requestType !== null) {
            return $requestType;
        }

        $deviceConfig = $this->findDeviceConfig($device, $people);
        if ($deviceConfig instanceof DeviceConfig) {
            return $this->normalizeDeviceConfigType($deviceConfig->getType());
        }

        return self::DEFAULT_DEVICE_CONFIG_TYPE;
    }

    public function discoveryDeviceConfig(
        Device $device,
        People $people,
        ?string $type = null
    ): DeviceConfig
    {
        $resolvedType = $this->resolveDeviceConfigType($device, $people, $type);
        $device_config = $this->manager->getRepository(DeviceConfig::class)->findOneBy([
            'device' => $device,
            'people' => $people,
            'type' => $resolvedType,
        ]);
        if (!$device_config) {
            $device_config = new DeviceConfig();
            $device_config->setDevice($device);
            $device_config->setPeople($people);
            $device_config->setType($resolvedType);
            $device_config->setConfigs([]);
            $this->manager->persist($device_config);
            return $device_config;
        }

        return $device_config;
    }

    public function addDeviceConfigs(
        People $people,
        array $configs,
        $deviceId,
        ?string $type = null
    )
    {
        $device = $this->discoveryDevice($deviceId);

        $device_config = $this->discoveryDeviceConfig($device,  $people, $type);
        foreach ($configs as $key => $config)
            $device_config->addConfig($key,  $config);

        $this->manager->persist($device_config);
        $this->manager->flush();

        return $device_config;
    }

    public function postPersist(Device $device): void
    {
        // DeviceConfig agora eh contextual por empresa + tipo.
        // O cadastro do device nao cria mais configuracoes vazias automaticamente.
    }

    public function securityFilter(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
    {
        $companies = $this->peopleService->getMyCompanies();
        $requestedDevice = trim((string) $this->request?->query->get('device', ''));
        $headerDevice = trim((string) $this->request?->headers->get('device', ''));
        $allowSelfDeviceWithoutConfig =
            $requestedDevice !== '' &&
            $headerDevice !== '' &&
            $requestedDevice === $headerDevice;

        if (empty($companies)) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        $aliases = $queryBuilder->getAllAliases();
        if (!in_array('DeviceCompanyConfig', $aliases, true)) {
            $queryBuilder->{$allowSelfDeviceWithoutConfig ? 'leftJoin' : 'innerJoin'}(
                DeviceConfig::class,
                'DeviceCompanyConfig',
                'WITH',
                sprintf('DeviceCompanyConfig.device = %s', $rootAlias)
            );
        }

        $queryBuilder->distinct();
        $queryBuilder->setParameter('companies', $companies);

        if ($allowSelfDeviceWithoutConfig) {
            $queryBuilder->andWhere(sprintf(
                '(DeviceCompanyConfig.people IN(:companies) OR %s.device = :selfDevice)',
                $rootAlias
            ));
            $queryBuilder->setParameter('selfDevice', $requestedDevice);
        } else {
            $queryBuilder->andWhere('DeviceCompanyConfig.people IN(:companies)');
        }

        if ($people = $this->request?->query->get('people', null)) {
            $queryBuilder->andWhere('DeviceCompanyConfig.people IN(:people)');
            $queryBuilder->setParameter('people', preg_replace("/[^0-9]/", "", $people));
        }
    }
}
