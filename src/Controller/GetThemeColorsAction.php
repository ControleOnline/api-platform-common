<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\PeopleDomain;
use Symfony\Component\HttpFoundation\Request;
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
        $this->manager     = $manager;
    }

    public function __invoke(Request $request)
    {
        $domain = preg_replace("/[^a-zA-Z0-9.:_-]/", "", str_replace(
            ['https://', 'http://'],
            '',
            $request->headers->get('referer')
        ));
        $peopleDomain = $this->manager->getRepository(PeopleDomain::class)->findBy(['domain' => $domain]);
        $theme = $peopleDomain->getTheme();

        foreach ($theme->getColors(true) as $index => $color) {
            echo $index . ': ' . $color . ';';
        }
    }
}
