<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Device;
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

    public function generatePrintData(Device $device): Spool
    {
        $content =  [
            "operation" => "PRINT_TEXT",
            "styles" => [[]],
            "value" => [$this->text]
        ];

        return  $this->addToSpool($device, json_encode($content, true));
    }

    public function addToSpool(Device $device, string  $content): Spool
    {
        $user = $this->security->getToken()->getUser();
        $status = $this->statusService->discoveryStatus('open', 'open', 'print');
        $file = $this->fileService->addFile($user->getPeople(), $content, 'print', 'print', 'text', 'txt');
        $spool = new Spool();
        $spool->setDevice($device);
        $spool->setStatus($status);
        $spool->setFile($file);
        $spool->setUser($user);
        $this->entityManager->persist($spool);
        $this->entityManager->flush();
      
        return $spool;
    }
}
