<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Spool;
use ControleOnline\Service\HydratorService;
use Symfony\Component\Security\Http\Attribute\Security;
use ControleOnline\Service\PrintService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class PrintController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private PrintService $printService,
        private HydratorService $hydratorService
    ) {}
    #[Route('/print/{id}/done', name: "print_done", methods: ["PUT"])]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_CLIENT')")]
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
