<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\DomainService;
use ControleOnline\Service\ServerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GetServerByDomainAction extends AbstractController
{
    public function __construct(
        private ServerService $serverService,
        private DomainService $domainService,
    ) {}

    #[Route('/people-domains/server', name: 'people_domain_server', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $domain = trim((string) $request->query->get('domain', ''));
        if ($domain === '') {
            $domain = trim((string) $this->domainService->getDomain());
        }

        $server = $this->serverService->findByDomain($domain);

        return new JsonResponse([
            'domain' => $domain,
            'server' => $server,
        ]);
    }
}
