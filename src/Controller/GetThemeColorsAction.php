<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Service\DomainService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class GetThemeColorsAction
{

    public function __construct(
        private EntityManagerInterface $manager,
        private DomainService $domainService
    ) {
    }

    public function __invoke(Request $request)
    {
        $domain = $this->domainService->getDomain($request);
        $peopleDomain = $this->manager->getRepository(PeopleDomain::class)->findOneBy(['domain' => $domain]);

        if (!$peopleDomain) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $theme = $peopleDomain->getTheme();
        $css = ':root{' . PHP_EOL;
        foreach ($theme->getColors(true) as $index => $color) {
            $css .= '    --q-' . $index . ': ' . $color . ';' . PHP_EOL;
            $css .= '    --'   . $index . ': ' . $color . ';' . PHP_EOL;
        }
        $css .= '}';
        return new Response($css, Response::HTTP_OK, [
            'Content-Type' => 'text/css',
        ]);
    }
}
