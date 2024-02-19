<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\PeopleDomain;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class GetThemeColorsAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function __invoke(Request $request)
    {
        $domain = $this->getDomain($request);
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


    /**
     * @param Request $request
     * @return string
     */
    private function getDomain(Request $request)
    {

        $domain = preg_replace("/[^a-zA-Z0-9.:_-]/", "", str_replace(
            ['https://', 'http://'],
            '',
            $request->get(
                'app-domain',
                $request->headers->get(
                    'app-domain',
                    $request->headers->get(
                        'referer',
                        $request->server->get('HTTP_HOST')
                    )
                )
            )
        ));
        return $domain;
    }
}
