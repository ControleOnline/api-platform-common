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
        $printer = null;
        $device_config =  $this->deviceService->discoveryDeviceConfig($device, $provider)->getConfigs(true);
        if (isset($device_config['printer']))
            $printer = $this->deviceService->discoveryDevice($device_config['printer']);

        $text = $this->text;
        $this->text = '';

        $content =  [
            "operation" => "PRINT_TEXT",
            "styles" => [(object) []],
            "value" => [$text]
        ];

        $printData = $this->addToSpool($printer ?: $device, $provider, json_encode($content), $aditionalData);

        if ($printer != $device)
            $x = '';

        return $printData;
    }

    private function resolveNotificationDevice(Device $printer, People $provider): Device
    {
        $deviceConfig = $this->entityManager->getRepository(DeviceConfig::class)->findOneBy([
            'device' => $printer,
            'people' => $provider,
        ]);

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

    public function addToSpool(Device $printer, People $provider, string  $content, ?array $data = []): Spool
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

        $notificationDevice = $this->resolveNotificationDevice($printer, $provider);
        $this->websocketClient->push($notificationDevice, json_encode($data));

        return $spool;
    }
}
