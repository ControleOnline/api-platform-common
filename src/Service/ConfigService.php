<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\PeopleDomain;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;
use Doctrine\ORM\EntityManagerInterface;


class ConfigService
{
    private $request;
    public function __construct(
        private EntityManagerInterface $manager,
        private RequestStack $requestStack
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getConfig(){
        
    }
    
}
