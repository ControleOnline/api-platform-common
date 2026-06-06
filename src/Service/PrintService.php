<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Device;
use ControleOnline\Entity\DeviceConfig;
use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Spool;
use ControleOnline\Entity\User;
use ControleOnline\Service\Client\WebsocketClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
as Security;

class PrintService
{
    private string $printDeviceType = 'PRINT';
    private string $printerDeviceType = 'PRINTER';
    private string $pdvDeviceType = 'PDV';
    private string $displayDeviceType = 'DISPLAY';
    private string $networkPrinterProtocol = 'network-ip-raw';
    private string $pdvPrinterProtocol = 'pdv-text';
    private string $networkCutMarker = '[__PRINT_CUT__]';
    private string $networkCutCommand = "\x1D\x56\x00";
    private string $networkCutSpacing = "\n\n\n";
    private bool $binaryOutput = false;
    private $initialSpace = 8;
    private $totalChars = 48;
    private $text = '';
    private string $networkPrinterManagerDeviceConfigKey = 'print-network-manager-device';
    protected static $logger;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerService $loggerService,
        private FileService $fileService,
        private StatusService $statusService,
        private DeviceService $deviceService,
        private Security $security,
        private WebsocketClient $websocketClient,
    ) {
        self::$logger = $loggerService->getLogger('print');
    }

    public function addLine($prefix = '', $suffix = '', $delimiter = ' ')
    {
        $initialSpace = str_repeat(" ", $this->initialSpace);
        $count =   $this->totalChars - $this->initialSpace - strlen($prefix) - strlen($suffix);
        if ($count > 0)
            $delimiter = str_repeat($delimiter, $count);
        $this->text .= $initialSpace . $prefix . $delimiter . $suffix . "\n";
    }

    public function addCutMarker(): void
    {
        if ($this->binaryOutput) {
            $this->text = rtrim($this->text, "\n") . "\n" . $this->networkCutCommand;
            return;
        }

        $this->text = rtrim($this->text, "\n") . "\n" . $this->networkCutMarker . "\n";
    }

    // Queue conference tickets need a machine-readable barcode without changing the plain-text flow.
    public function addCode128Barcode(
        string $value,
        int $width = 2,
        int $height = 80,
        int $hriPosition = 2
    ): void {
        $normalizedValue = trim($value);
        if ($normalizedValue === '') {
            return;
        }

        $this->binaryOutput = true;
        $this->text = rtrim($this->text, "\n") . "\n" . $this->buildCode128BarcodeBytes(
            $normalizedValue,
            $width,
            $height,
            $hriPosition
        ) . "\n";
    }

    public function makePrintDone(Spool $spool): void
    {
        $connection = $this->entityManager->getConnection();
        $fileId = $spool->getFile()->getId();

        $connection->beginTransaction();
        try {
            $this->entityManager->remove($spool);
            $this->entityManager->flush();

            $file = $this->entityManager->find(File::class, $fileId);
            if ($file instanceof File) {
                $this->entityManager->remove($file);
                $this->entityManager->flush();
            }

            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }

    public function generatePrintData(Device $device, People $provider, ?array $aditionalData = []): Spool
    {
        $requestedDeviceType = $this->resolveAdditionalDataDeviceType($aditionalData);
        $printer = $this->resolveSpoolTargetDevice(
            $device,
            $provider,
            $requestedDeviceType
        );
        $resolvedPrinter = $printer ?: $device;
        $resolvedPrinterType = $printer instanceof Device
            ? (
                $this->resolveConfiguredPrinterTargetType(
                    $device,
                    $provider,
                    $requestedDeviceType
                ) ??
                $this->resolvePrinterDeviceType($resolvedPrinter, $provider)
            )
            : $requestedDeviceType;
        $binaryOutput = $this->binaryOutput;
        $text = $this->text;
        $this->text = '';
        $this->binaryOutput = false;
        $printProtocol = $this->resolvePrintProtocol(
            $resolvedPrinter,
            $provider,
            $resolvedPrinterType
        );
        $content = $this->buildPrintContent($text, $printProtocol, $binaryOutput);

        return $this->addToSpool(
            $resolvedPrinter,
            $provider,
            $content,
            array_merge($aditionalData ?? [], [
                'printProtocol' => $printProtocol,
                'type' => $resolvedPrinterType ?: $requestedDeviceType,
            ]),
            $this->resolveNotificationDevice(
                $resolvedPrinter,
                $provider,
                $device,
                $resolvedPrinterType,
                $requestedDeviceType
            )
        );
    }

    private function resolveAdditionalDataDeviceType(?array $aditionalData = []): ?string
    {
        $candidate = trim((string) (
            $aditionalData['type'] ??
            $aditionalData['deviceType'] ??
            ''
        ));

        return $candidate !== '' ? $this->normalizeDeviceType($candidate) : null;
    }

    private function resolveSpoolTargetDevice(
        Device $device,
        People $provider,
        ?string $type = null
    ): ?Device
    {
        $deviceConfig = $this->deviceService->findDeviceConfig($device, $provider, $type);
        $configs = $deviceConfig?->getConfigs(true);

        if (!is_array($configs) || !isset($configs['printer'])) {
            return null;
        }

        $configuredPrinter = trim((string) $configs['printer']);
        if ($configuredPrinter === '') {
            return null;
        }

        $printerDeviceConfig = $this->deviceService->findDeviceConfigByReference(
            $configuredPrinter,
            $provider
        );
        if ($printerDeviceConfig instanceof DeviceConfig) {
            return $printerDeviceConfig->getDevice();
        }

        if ($this->deviceService->isDeviceConfigReference($configuredPrinter)) {
            return null;
        }

        return $this->deviceService->discoveryDevice($configuredPrinter);
    }

    private function resolveConfiguredPrinterTargetType(
        Device $device,
        People $provider,
        ?string $type = null
    ): ?string {
        $deviceConfig = $this->deviceService->findDeviceConfig($device, $provider, $type);
        $configs = $deviceConfig?->getConfigs(true);

        if (!is_array($configs) || !isset($configs['printer'])) {
            return null;
        }

        $configuredPrinter = trim((string) $configs['printer']);
        if ($configuredPrinter === '') {
            return null;
        }

        $printerDeviceConfig = $this->deviceService->findDeviceConfigByReference(
            $configuredPrinter,
            $provider
        );

        return $printerDeviceConfig instanceof DeviceConfig
            ? $this->normalizeDeviceType($printerDeviceConfig->getType())
            : null;
    }

    private function normalizeDeviceType(?string $deviceType): string
    {
        return strtoupper(trim((string) $deviceType));
    }

    private function resolvePrinterDeviceType(Device $device, People $provider): ?string
    {
        foreach ($this->deviceService->findDeviceConfigs($device, $provider) as $deviceConfig) {
            $normalizedType = $this->normalizeDeviceType($deviceConfig?->getType());
            if (in_array($normalizedType, [$this->printDeviceType, $this->printerDeviceType], true)) {
                return $normalizedType;
            }
        }

        return null;
    }

    private function resolveConfiguredDeviceType(
        Device $device,
        People $provider,
        ?string $type = null
    ): string
    {
        $resolvedType = trim((string) $type) !== ''
            ? $this->normalizeDeviceType($type)
            : '';
        $deviceConfig = $this->deviceService->findDeviceConfig($device, $provider, $resolvedType);

        return $deviceConfig instanceof DeviceConfig
            ? $this->normalizeDeviceType($deviceConfig->getType())
            : $resolvedType;
    }

    private function isNetworkPrinterDevice(
        Device $device,
        People $provider,
        ?string $type = null
    ): bool
    {
        return in_array(
            $this->resolveConfiguredDeviceType($device, $provider, $type),
            [$this->printDeviceType, $this->printerDeviceType],
            true
        );
    }

    private function resolvePrintProtocol(
        Device $device,
        People $provider,
        ?string $type = null
    ): string
    {
        $deviceType = $this->resolveConfiguredDeviceType($device, $provider, $type);

        if (in_array($deviceType, [$this->printDeviceType, $this->printerDeviceType], true)) {
            return $this->networkPrinterProtocol;
        }

        if ($deviceType === $this->pdvDeviceType) {
            return $this->pdvPrinterProtocol;
        }

        return $this->pdvPrinterProtocol;
    }

    private function buildPrintContent(string $text, string $printProtocol, bool $binaryOutput = false): string
    {
        if ($binaryOutput) {
            return base64_encode($text);
        }

        if ($printProtocol === $this->networkPrinterProtocol) {
            return $this->buildNetworkPrintContent($text);
        }

        return $this->buildPdvPrintContent($text);
    }

    private function buildCode128BarcodeBytes(
        string $value,
        int $width = 2,
        int $height = 80,
        int $hriPosition = 2
    ): string {
        $normalizedWidth = max(2, min(6, $width));
        $normalizedHeight = max(1, min(255, $height));
        $normalizedHriPosition = in_array($hriPosition, [0, 1, 2, 3], true)
            ? $hriPosition
            : 2;

        return pack(
            'C*',
            29, 119, $normalizedWidth,
            29, 104, $normalizedHeight,
            29, 72, $normalizedHriPosition,
            29, 107, 79, strlen($value)
        ) . $value;
    }

    private function buildPdvPrintContent(string $text): string
    {
        $normalizedText = str_replace($this->networkCutMarker, '', $text);
        $content = [
            "operation" => "PRINT_TEXT",
            "styles" => [(object) []],
            "value" => [$normalizedText]
        ];

        return json_encode($content);
    }

    private function buildNetworkPrintContent(string $text): string
    {
        $normalizedText = str_replace(["\r\n", "\r"], "\n", $text);
        $cutMarkerCount = substr_count($normalizedText, $this->networkCutMarker);

        if ($cutMarkerCount === 0) {
            $payload = rtrim($normalizedText, "\n") . $this->networkCutSpacing . $this->networkCutCommand;
            return $payload;
        }

        $segments = explode($this->networkCutMarker, $normalizedText);
        $payload = '';

        foreach ($segments as $index => $segment) {
            $trimmedSegment = rtrim($segment, "\n");
            if ($trimmedSegment !== '') {
                $payload .= $trimmedSegment . "\n";
            }

            if ($index < $cutMarkerCount) {
                $payload .= $this->networkCutSpacing . $this->networkCutCommand;
            }
        }

        return rtrim($payload, "\n");
    }

    private function resolveNotificationDevice(
        Device $printer,
        People $provider,
        ?Device $gatewayDevice = null,
        ?string $printerType = null,
        ?string $gatewayType = null
    ): Device
    {
        if (
            $gatewayDevice instanceof Device &&
            $gatewayDevice->getId() !== $printer->getId() &&
            $this->isNetworkPrinterDevice($printer, $provider, $printerType) &&
            $this->resolveConfiguredDeviceType($gatewayDevice, $provider, $gatewayType) === $this->displayDeviceType
        ) {
            return $gatewayDevice;
        }

        $deviceConfig = $this->deviceService->findDeviceConfig($printer, $provider, $printerType);

        if (!$deviceConfig instanceof DeviceConfig) {
            return $printer;
        }

        $configs = $deviceConfig->getConfigs(true);
        $managerDeviceId = trim((string) (
            $configs[$this->networkPrinterManagerDeviceConfigKey] ?? ''
        ));

        if ($managerDeviceId === '') {
            return $printer;
        }

        $managerDevice = $this->entityManager->getRepository(Device::class)->findOneBy([
            'device' => $managerDeviceId,
        ]);

        return $managerDevice instanceof Device ? $managerDevice : $printer;
    }

    public function addToSpool(
        Device $printer,
        People $provider,
        string  $content,
        ?array $data = [],
        ?Device $notificationDevice = null
    ): Spool
    {
        $user = $this->security->getToken()?->getUser();
        $status = $this->statusService->discoveryStatus('open', 'open', 'print');
        $fileOwner = $user instanceof User ? $user->getPeople() : $provider;
        $file = $this->fileService->addFile($fileOwner, $content, 'print', 'print', 'text', 'txt');
        self::$logger->error($printer->getDevice());
        self::$logger->error($printer->getId());

        $spool = new Spool();
        $spool->setDevice($printer);
        $spool->setStatus($status);
        $spool->setFile($file);
        if ($user instanceof User) {
            $spool->setUser($user);
        }
        $this->entityManager->persist($spool);
        $this->entityManager->flush();

        $data["action"] = "print";
        $data["store"] = "print";
        $data["spoolId"] = $spool->getId();
        $data["spool"] = '/spools/' . $spool->getId();
        $data["device"] = $printer->getDevice();
        $data["deviceId"] = $printer->getId();
        $data["deviceType"] =
            $this->resolveAdditionalDataDeviceType($data) ??
            $this->resolvePrinterDeviceType($printer, $provider) ??
            $this->resolveConfiguredDeviceType($printer, $provider);

        $targetNotificationDevice = $notificationDevice instanceof Device
            ? $notificationDevice
            : $this->resolveNotificationDevice($printer, $provider);
        $this->websocketClient->push($targetNotificationDevice, json_encode($data));

        return $spool;
    }
}
