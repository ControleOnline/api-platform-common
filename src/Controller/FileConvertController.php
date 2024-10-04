<?php

namespace ControleOnline\Controller;

use Symfony\Component\HttpFoundation\Response;
use ControleOnline\Entity\File;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Service\HydratorService;
use ControleOnline\Service\PdfService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

class FileConvertController
{

    public function __construct(
        private EntityManagerInterface $em,
        private HydratorService $hydratorService,
        private PdfService $pdf
    ) {}

    public function __invoke(File $data): Response
    {

        try {

            if ($data->getFileType() == 'text' && $data->getExtension() == 'html') {

                $data->setFileType('application');
                $data->setExtension('pdf');
                $data->setContent($this->pdf->convertHtmlToPdf($data->getContent()));
                $this->em->persist($data);
                $this->em->flush();
            }
            return new JsonResponse($this->hydratorService->data($data, 'file_read'), Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse($this->hydratorService->error($e));
        }
    }
}
