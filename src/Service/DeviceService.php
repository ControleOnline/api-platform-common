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
        $normalizedDeviceId = trim((string) $deviceId);
        if ($normalizedDeviceId === '') {
            throw new \InvalidArgumentException('Device identifier is required.');
        }

        $device = $this->manager->getRepository(Device::class)->findOneBy([
            'device' => $normalizedDeviceId,
        ]);
        if ($device instanceof Device) {
            return $device;
        }

        $this->manager->getConnection()->executeStatement(
            'INSERT INTO device (device) VALUES (:device) ON DUPLICATE KEY UPDATE id = id',
            ['device' => $normalizedDeviceId]
        );

        $device = $this->manager->getRepository(Device::class)->findOneBy([
            'device' => $normalizedDeviceId,
        ]);
        if (!$device instanceof Device) {
            throw new \RuntimeException('Unable to resolve device after persistence.');
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

    public function decodePayloadContent(?string $content): array
    {
        return $this->decodePayload($content);
    }

    public function resolveDeviceReference(mixed $reference): ?Device
    {
        $normalizedReference = trim((string) $reference);
        if ($normalizedReference === '') {
            return null;
        }

        $device = $this->manager->getRepository(Device::class)->findOneBy([
            'device' => $normalizedReference,
        ]);

        if ($device instanceof Device) {
            return $device;
        }

        if (
            preg_match('#/devices/(\d+)$#', $normalizedReference, $matches) === 1
        ) {
            return $this->manager->getRepository(Device::class)->find(
                (int) $matches[1]
            );
        }

        return null;
    }

    public function resolvePeopleReference(mixed $reference): ?People
    {
        return $this->manager->getRepository(People::class)->find(
            preg_replace("/[^0-9]/", "", (string) $reference)
        );
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

    public function addDeviceConfigFromPayload(
        Request $request,
        array $payload = []
    ): DeviceConfig {
        $device = $this->resolveDeviceIdentifier($request, $payload);

        if ($device === '') {
            throw new \InvalidArgumentException(
                'DEVICE header or body field "device" is required.'
            );
        }

        $people = $this->resolvePeopleReference($payload['people'] ?? '');
        if (!$people instanceof People) {
            throw new \InvalidArgumentException('People not found');
        }

        return $this->addDeviceConfigs(
            $people,
            $this->normalizeDeviceConfigsPayload($payload['configs'] ?? []),
            $device,
            $this->resolveDeviceConfigTypeFromRequest($request, $payload)
        );
    }

    public function addDeviceConfigFromContent(
        Request $request,
        ?string $content
    ): DeviceConfig {
        return $this->addDeviceConfigFromPayload(
            $request,
            $this->decodePayload($content)
        );
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

    private function decodePayload(?string $content): array
    {
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
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

        $form = $this->request->request->all();
        $query = $this->request->query->all();
        $candidates = [
            $payload['deviceType'] ?? null,
            $form['deviceType'] ?? null,
            $query['deviceType'] ?? null,
            $this->request->headers->get('device-type'),
        ];

        foreach ($candidates as $candidate) {
            if (!is_scalar($candidate)) {
                continue;
            }

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
        $selfDevice = $this->resolveSelfDeviceIdentifier();
        $allowSelfDeviceWithoutConfig = $selfDevice !== '';

        if (empty($companies) && !$allowSelfDeviceWithoutConfig) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        if (empty($companies)) {
            $queryBuilder->andWhere(sprintf('%s.device = :selfDevice', $rootAlias));
            $queryBuilder->setParameter('selfDevice', $selfDevice);
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
            $queryBuilder->setParameter('selfDevice', $selfDevice);
        } else {
            $queryBuilder->andWhere('DeviceCompanyConfig.people IN(:companies)');
        }

        if ($people = $this->request?->query->get('people', null)) {
            $queryBuilder->andWhere('DeviceCompanyConfig.people IN(:people)');
            $queryBuilder->setParameter('people', preg_replace("/[^0-9]/", "", $people));
        }
    }

    private function resolveSelfDeviceIdentifier(): string
    {
        if (!$this->request instanceof Request) {
            return '';
        }

        $headerDevice = trim((string) $this->request->headers->get('device', ''));
        if ($headerDevice === '') {
            return '';
        }

        $requestedDevice = trim((string) $this->request->query->get('device', ''));
        if ($requestedDevice === '') {
            $payload = $this->decodePayload($this->request->getContent());
            $requestedDevice = trim((string) ($payload['device'] ?? ''));
        }

        if ($requestedDevice === '' || $requestedDevice !== $headerDevice) {
            return '';
        }

        return $headerDevice;
    }
}
