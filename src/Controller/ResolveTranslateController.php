<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\HydratorService;
use ControleOnline\Service\TranslateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/translates/resolve', methods: ['POST'])]
class ResolveTranslateController extends AbstractController
{
    public function __construct(
        private HydratorService $hydrator,
        private TranslateService $translateService
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            $result = $this->translateService->resolveFromPayload(
                json_decode($request->getContent() ?: '[]', true) ?: []
            );

            return new Response(
                json_encode($this->hydrator->result($result)),
                200,
                ['Content-Type' => 'application/ld+json']
            );
        } catch (\Exception $e) {
            $statusCode = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return new Response(
                json_encode($this->hydrator->error($e)),
                $statusCode,
                ['Content-Type' => 'application/ld+json']
            );
        }
    }
}
