<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\PeopleDomain;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use InvalidArgumentException;
use Doctrine\ORM\EntityManagerInterface;


class DomainService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private RequestStack $requestStack,
    ) {}

    private ?PeopleDomain $peopleDomain = null;
    private ?string $peopleDomainKey = null;

    /**
     * @return string
     */
    public function getDomain()
    {
        $request = $this->requestStack->getCurrentRequest();
        $domainSource = $request
            ? $this->resolveRequestDomain($request) ?? $this->getMainDomain()
            : $this->getMainDomain();

        $domain = preg_replace(
            "/[^a-zA-Z0-9.:_-]/",
            '',
            str_replace(['https://', 'http://'], '', $domainSource),
        );

        if (!$domain)
            throw new InvalidArgumentException('Please define header or get param "app-domain"', 301);
        return $domain;
    }

    public function getMainDomain()
    {
        return $this->requestStack->getCurrentRequest()?->server->get('HTTP_HOST') ?: 'api.controleonline.com';
    }

    private function resolveRequestDomain(Request $request): ?string
    {
        $candidateValues = [
            $request->attributes->get('App-domain'),
            $request->attributes->get('app-domain'),
            $request->query->get('App-domain'),
            $request->query->get('app-domain'),
            $request->headers->get('app-domain'),
            $request->headers->get('referer'),
        ];

        foreach ($candidateValues as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return $candidate;
            }
        }

        return null;
    }

    public function getPeopleDomain(): PeopleDomain
    {
        $domain = $this->getDomain();

        if ($this->peopleDomainKey === $domain && $this->peopleDomain) {
            return $this->peopleDomain;
        }

        $peopleDomain = $this->manager->getRepository(PeopleDomain::class)->findOneBy(['domain' => $domain]);

        if (!$peopleDomain) {
            $domain = $this->getMainDomain();
            $peopleDomain = $this->manager->getRepository(PeopleDomain::class)->findOneBy(['domain' => $domain]);
        }

        if ($peopleDomain === null)
            throw new \Exception(
                sprintf('Main company "%s" not found', $domain)
            );

        $this->peopleDomainKey = $domain;
        $this->peopleDomain = $peopleDomain;

        return $peopleDomain;
    }
}
