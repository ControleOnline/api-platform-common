<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\File;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\JsonResponse;



class GetFileDataAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Synfony Kernel
     *
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(EntityManagerInterface $entityManager, KernelInterface $appKernel)
    {
        $this->kernel  = $appKernel;
        $this->manager = $entityManager;
    }

    public function __invoke(File $data,  Request $request)
    {

        try {



            return new JsonResponse([
                'response' => [
                    'data'    => [],
                    'count'   => 0,
                    'error'   => '',
                    'success' => false,
                ],
            ]);
            $file = $data;
            //$file = $this->manager->getRepository(File::class)->findOneBy(['url' => $request->getPathInfo()]);
            if (!$file)
                throw new \Exception('Not found', 404);


            $content  = $file->getContent();
            $response = new StreamedResponse(function () use ($content) {
                fputs(fopen('php://output', 'wb'), $content);
            });

            $fileType = $file->getFileType();
            $ext = $file->getExtension();
            if ($ext == 'svg') {
                $response->headers->set('Content-Type', 'image/svg+xml');
            } else {
                $response->headers->set('Content-Type', "$fileType/$ext");
            }

            if ($fileType == 'image')
                $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, basename($request->getPathInfo()));
            else
                $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, basename($request->getPathInfo()));

            $response->headers->set('Content-Disposition', $disposition);

            return $response;
        } catch (\Exception $e) {
            return new JsonResponse([
                'response' => [
                    'data'    => [],
                    'count'   => 0,
                    'error'   => $e->getMessage(),
                    'success' => false,
                ],
            ]);
        }
    }
}
