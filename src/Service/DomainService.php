<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\PeopleDomain;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;
use Doctrine\ORM\EntityManagerInterface;

class DomainService
{
    private ?PeopleDomain $peopleDomain = null;
    private ?Request $request;

    public function __construct(
        private EntityManagerInterface $manager,
        RequestStack $requestStack
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getDomain(): string
    {
        $domain = !$this->request
            ? $this->getMainDomain()
            : preg_replace(
                "/[^a-zA-Z0-9.:_-]/",
                "",
                str_replace(
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
                )
            );

        if (!$domain) {
            throw new InvalidArgumentException(
                'Please define header or get param "app-domain"',
                301
            );
        }

        return $domain;
    }

    public function getMainDomain(): string
    {
        return $this->request
            ? $this->request->server->get('HTTP_HOST')
            : 'api.controleonline.com';
    }

    public function getPeopleDomain(): PeopleDomain
    {
        if ($this->peopleDomain) {
            return $this->peopleDomain;
        }

        $domain = $this->getDomain();

        $this->peopleDomain = $this->manager
            ->getRepository(PeopleDomain::class)
            ->findOneBy(['domain' => $domain]);

        if (!$this->peopleDomain) {

            $domain = $this->getMainDomain();

            $this->peopleDomain = $this->manager
                ->getRepository(PeopleDomain::class)
                ->findOneBy(['domain' => $domain]);
        }

        if ($this->peopleDomain === null) {
            throw new \Exception(
                sprintf('Main company "%s" not found', $domain)
            );
        }

        return $this->peopleDomain;
    }
}
