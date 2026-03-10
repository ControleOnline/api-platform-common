<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\File;
use ControleOnline\Entity\Import;
use ControleOnline\Entity\People;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ImportUploadController extends AbstractController
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(Request $request): Import
    {

        $importType = $request->request->get('importType');

        $uploadedFile = $request->files->get('file');

        if (!$importType) {
            throw new BadRequestHttpException('importType is required');
        }

        if (!$uploadedFile) {
            throw new BadRequestHttpException('CSV file is required');
        }

        $extension = strtolower($uploadedFile->getClientOriginalExtension());

        if ($extension !== 'csv') {
            throw new BadRequestHttpException('Only CSV files are allowed');
        }


        $file = new File();

        $file->setFileName($uploadedFile->getClientOriginalName());
        $file->setExtension('csv');
        $file->setFileType('import');
        $file->setContext('import');
        $file->setContent(file_get_contents($uploadedFile->getPathname()));

        /** @var People $people */
        $people = $this->getUser()->getPeople();

        if ($people) {
            $file->setPeople($people);
        }

        $this->em->persist($file);



        $import = new Import();

        $import->setImportType($importType);
        $import->setFileFormat('csv');
        $import->setFile($file);

        if ($people) {
            $import->setPeople($people);
        }

        $this->em->persist($import);

        $this->em->flush();

        return $import;
    }
}
