<?php

namespace App\Controller;

use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\Theme;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;



class AdminPersonRegionsAction
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Request
     *
     * @var Request
     */
    private $request  = null;


    public function __construct(EntityManagerInterface $manager, Security $security)
    {
        $this->manager     = $manager;
        $this->security    = $security;
        $this->currentUser = $security->getUser();
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
