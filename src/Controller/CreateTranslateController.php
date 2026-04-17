<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\HydratorService;
use ControleOnline\Service\TranslateService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CreateTranslateController extends AbstractController
{
    public function __construct(
        private HydratorService $hydrator,
        private TranslateService $translateService
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            $result = $this->translateService->createFromContent($request->getContent());

            return new Response(
                json_encode(
                    $this->hydrator->data($result, ['translate:read'])
                ),
                200,
                ['Content-Type' => 'application/ld+json']
            );
        } catch (\Exception $e) {

            return new Response(
                json_encode($this->hydrator->error($e)),
                400,
                ['Content-Type' => 'application/ld+json']
            );
        }
    }
}
