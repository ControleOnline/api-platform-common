<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Spool;
use ControleOnline\Service\HydratorService;
use ControleOnline\Service\PrintService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PrintController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private PrintService $printService,
        private HydratorService $hydratorService
    ) {}
    #[Route('/print/{id}/done', name: "print_done", methods: ["GET"])]
    public function makePrintDone(Spool $spool): JsonResponse
    {
        try {
            $spool = $this->printService->makePrintDone($spool);
            return new JsonResponse($this->hydratorService->item(Spool::class, $spool->getId(), "spool_item:write"), Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse($this->hydratorService->error($e));
        }
    }
}
