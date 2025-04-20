<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Device;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Spool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
as Security;

class PrintService
{
    private $initialSpace = 8;
    private $totalChars = 48;
    private $text = '';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileService $fileService,
        private StatusService $statusService,
        private DeviceService $deviceService,
        private Security $security
    ) {}

    public function addLine($prefix = '', $suffix = '', $delimiter = ' ')
    {
        $initialSpace = str_repeat(" ", $this->initialSpace);
        $count =   $this->totalChars - $this->initialSpace - strlen($prefix) - strlen($suffix);
        if ($count > 0)
            $delimiter = str_repeat($delimiter, $count);
        $this->text .= $initialSpace . $prefix . $delimiter . $suffix . "\n";
    }

    public function makePrintDone(Spool $spool): Spool
    {
        $status = $this->statusService->discoveryStatus('closed', 'done', 'print');
        $spool->setStatus($status);
        $this->entityManager->persist($spool);
        $this->entityManager->flush();
        return  $spool;
    }

    public function generatePrintData(Device $device, People $provider): Spool
    {
        $printer = null;
        $device_config =  $this->deviceService->discoveryDeviceConfig($device, $provider)->getConfigs(true);
        if (isset($device_config['printer']))
            $printer = $this->deviceService->discoveryDevice($device_config['printer']);

        error_log($printer->getDevice());

        $content =  [
            "operation" => "PRINT_TEXT",
            "styles" => [[]],
            "value" => [$this->text]
        ];

        $printData = $this->addToSpool($printer ?: $device, json_encode($content));

        if ($printer != $device)
            $x = '';

        return $printData;
    }

    public function addToSpool(Device $printer, string  $content): Spool
    {
        $user = $this->security->getToken()->getUser();
        $status = $this->statusService->discoveryStatus('open', 'open', 'print');
        $file = $this->fileService->addFile($user->getPeople(), $content, 'print', 'print', 'text', 'txt');

        $spool = new Spool();
        $spool->setDevice($printer);
        $spool->setStatus($status);
        $spool->setFile($file);
        $spool->setUser($user);
        $this->entityManager->persist($spool);
        $this->entityManager->flush();

        return $spool;
    }
}
