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

        $domain = $this->normalizeDomainCandidate($domainSource);

        if ($domain === null)
            throw new InvalidArgumentException('Please define header or get param "app-domain"', 301);
        return $domain;
    }

    public function getMainDomain()
    {
        $requestHost = $this->requestStack->getCurrentRequest()?->server->get('HTTP_HOST');
        if (is_string($requestHost) && trim($requestHost) !== '') {
            return $requestHost;
        }

        $configuredDomain = $this->resolveConfiguredDomain();
        if ($configuredDomain !== null) {
            return $configuredDomain;
        }

        return 'api.controleonline.com';
    }

    private function resolveRequestDomain(Request $request): ?string
    {
        $candidateValues = [
            $request->attributes->get('App-domain'),
            $request->attributes->get('app-domain'),
            $request->query->get('App-domain'),
            $request->query->get('app-domain'),
            $request->headers->get('app-domain'),
            $request->headers->get('origin'),
            $request->headers->get('referer'),
        ];

        foreach ($candidateValues as $candidate) {
            $domain = $this->normalizeDomainCandidate($candidate);

            if ($domain !== null) {
                return $domain;
            }
        }

        return null;
    }

    private function normalizeDomainCandidate(mixed $candidate): ?string
    {
        if (!is_string($candidate)) {
            return null;
        }

        $candidate = trim($candidate);
        if ($candidate === '') {
            return null;
        }

        $parsedHost = $this->extractHostFromUrl($candidate);
        if ($parsedHost !== null) {
            return $parsedHost;
        }

        $candidate = preg_replace('/[\\/?#].*$/', '', $candidate);
        $candidate = preg_replace(
            "/[^a-zA-Z0-9.:_-]/",
            '',
            $candidate,
        );

        return $candidate !== '' ? $candidate : null;
    }

    private function resolveConfiguredDomain(): ?string
    {
        $candidates = [
            $_ENV['APP_DOMAIN'] ?? null,
            $_SERVER['APP_DOMAIN'] ?? null,
            $_ENV['ADMIN_APP_DOMAIN'] ?? null,
            $_SERVER['ADMIN_APP_DOMAIN'] ?? null,
            $_ENV['PUBLIC_APP_DOMAIN'] ?? null,
            $_SERVER['PUBLIC_APP_DOMAIN'] ?? null,
            getenv('APP_DOMAIN') ?: null,
            getenv('ADMIN_APP_DOMAIN') ?: null,
            getenv('PUBLIC_APP_DOMAIN') ?: null,
            $_SERVER['HTTP_HOST'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $domain = $this->normalizeDomainCandidate($candidate);
            if ($domain !== null) {
                return $domain;
            }
        }

        return null;
    }

    private function extractHostFromUrl(string $candidate): ?string
    {
        if (!preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $candidate) && !str_starts_with($candidate, '//')) {
            return null;
        }

        $host = parse_url($candidate, PHP_URL_HOST);

        if (!is_string($host) || trim($host) === '') {
            return null;
        }

        $port = parse_url($candidate, PHP_URL_PORT);

        if (is_int($port)) {
            return sprintf('%s:%d', $host, $port);
        }

        return $host;
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
