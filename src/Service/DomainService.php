<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\PeopleDomain;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;
use Doctrine\ORM\EntityManagerInterface;


class DomainService
{
    private static $peopleDomain;
    private $request;
    public function __construct(
        private  EntityManagerInterface $manager,
        RequestStack $requestStack
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }
    /**
     * @return string
     */
    public function getDomain()
    {

        $domain = preg_replace("/[^a-zA-Z0-9.:_-]/", "", str_replace(
            ['https://', 'http://'],
            '',
            $this->request->get(
                'App-domain',
                $this->request->get(
                    'app-domain',
                    $this->request->headers->get(
                        'app-domain',
                        $this->request->headers->get(
                            'referer',
                            $this->getMainDomain()
                        )
                    )
                )
            )
        ));

        if (!$domain)
            throw new InvalidArgumentException('Please define header or get param "app-domain"', 301);
        return $domain;
    }

    public function getMainDomain()
    {
        return $this->request->server->get('HTTP_HOST');
    }

    public function getPeopleDomain(): PeopleDomain
    {
        if (self::$peopleDomain) return self::$peopleDomain;

        $domain  = $this->getDomain();
        self::$peopleDomain = $this->manager->getRepository(PeopleDomain::class)->findOneBy(['domain' => $domain]);

        if (!self::$peopleDomain) {
            $domain  = $this->getMainDomain();
            self::$peopleDomain = $this->manager->getRepository(PeopleDomain::class)->findOneBy(['domain' => $domain]);
        }

        if (self::$peopleDomain === null)
            throw new \Exception(
                sprintf('Main company "%s" not found', $domain)
            );

        return self::$peopleDomain;
    }
}
