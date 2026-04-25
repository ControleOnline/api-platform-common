<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class TechnicalConfigAccessService
{
    public const GOOGLE_OAUTH_CLIENT_ID_KEY = 'OAUTH_GOOGLE_CLIENT_ID';
    public const CIELO_CONFIG_KEY = 'CIELO';
    public const NEW_RELIC_CONFIG_KEY = 'NEW_RELIC';
    public const SPOTIFY_CONFIG_KEY = 'SPOTIFY';

    public function __construct(
        private PeopleRoleService $peopleRoleService,
        private PeopleService $peopleService,
    ) {}

    public function getTechnicalConfigKeys(): array
    {
        return [
            self::GOOGLE_OAUTH_CLIENT_ID_KEY,
            self::CIELO_CONFIG_KEY,
            self::NEW_RELIC_CONFIG_KEY,
            self::SPOTIFY_CONFIG_KEY,
            SystemLogConfigService::ERROR_EMAIL_ENABLED_KEY,
            SystemLogConfigService::ERROR_EMAIL_RECIPIENTS_KEY,
            SystemLogConfigService::POLICY_CONFIG_KEY,
            MaintenanceRoutineService::ROUTINES_CONFIG_KEY,
        ];
    }

    public function isTechnicalConfigKey(?string $configKey): bool
    {
        return in_array(
            trim((string) $configKey),
            $this->getTechnicalConfigKeys(),
            true
        );
    }

    public function canAccessMainCompanyTechnicalSettings(): bool
    {
        try {
            $mainCompanyId = $this->getMainCompanyId();
            if ($mainCompanyId === null) {
                return false;
            }

            $myPeople = $this->peopleService->getMyPeople();
            if ($myPeople && (int) $myPeople->getId() === $mainCompanyId) {
                return true;
            }

            foreach ($this->peopleService->getMyCompanies() as $company) {
                if ((int) $company->getId() === $mainCompanyId) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    public function getMainCompanyId(): ?int
    {
        try {
            return (int) $this->peopleRoleService->getMainCompany()->getId();
        } catch (\Throwable) {
            return null;
        }
    }

    public function assertCanManageConfig(?People $people, ?string $configKey): void
    {
        if (!$this->isTechnicalConfigKey($configKey)) {
            return;
        }

        $mainCompanyId = $this->getMainCompanyId();
        if (
            $mainCompanyId === null
            || !$people
            || (int) $people->getId() !== $mainCompanyId
            || !$this->canAccessMainCompanyTechnicalSettings()
        ) {
            throw new AccessDeniedException(
                'Configuracoes tecnicas so podem ser gerenciadas na empresa principal.'
            );
        }
    }
}
