<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;

class SystemLogConfigService
{
    public const ERROR_EMAIL_ENABLED_KEY = 'log-error-email-enabled';
    public const ERROR_EMAIL_RECIPIENTS_KEY = 'log-error-email-recipients';
    public const POLICY_CONFIG_KEY = 'log-policy';

    public const POLICY_ENTITY = 'entity';
    public const POLICY_GENERIC = 'generic';
    public const POLICY_OPERATION_PATTERNS = 'operation_patterns';
    public const POLICY_FRONTEND_DEBUG = 'frontend_debug';
    public const POLICY_BACKEND_ERROR = 'backend_error';

    private const SPECIAL_GENERIC_CHANNELS = [
        'backend-error' => self::POLICY_BACKEND_ERROR,
        'frontend-debug' => self::POLICY_FRONTEND_DEBUG,
    ];

    public function __construct(
        private ConfigService $configService,
        private PeopleRoleService $peopleRoleService,
    ) {}

    public function getMainCompany(): ?People
    {
        try {
            return $this->peopleRoleService->getMainCompany();
        } catch (\Throwable) {
            return null;
        }
    }

    public function getErrorEmailSettings(): array
    {
        $recipients = $this->normalizeRecipients(
            $this->getMainCompanyConfig(self::ERROR_EMAIL_RECIPIENTS_KEY)
        );

        return [
            'enabled' => $this->normalizeBoolean(
                $this->getMainCompanyConfig(self::ERROR_EMAIL_ENABLED_KEY)
            ) && $recipients !== [],
            'recipients' => $recipients,
        ];
    }

    public function getLogPolicy(): array
    {
        $policy = self::getDefaultPolicy();
        $savedPolicy = $this->normalizePolicy(
            $this->getMainCompanyConfig(self::POLICY_CONFIG_KEY)
        );

        foreach ($savedPolicy as $policyKey => $config) {
            if (!isset($policy[$policyKey]) || !is_array($config)) {
                continue;
            }

            $policy[$policyKey] = array_merge($policy[$policyKey], $config);
        }

        return $policy;
    }

    public function shouldPersist(string $type, ?string $channel = null): bool
    {
        $policy = $this->resolvePolicyEntry($type, $channel);

        return (bool) ($policy['enabled'] ?? true);
    }

    public function resolveRetentionDays(string $type, ?string $channel = null): ?int
    {
        $policy = $this->resolvePolicyEntry($type, $channel);
        $retentionDays = $policy['retentionDays'] ?? null;

        if ($retentionDays === null || $retentionDays === '') {
            return null;
        }

        return is_numeric($retentionDays) && (int) $retentionDays > 0
            ? (int) $retentionDays
            : null;
    }

    public function resolvePolicyKey(string $type, ?string $channel = null): string
    {
        $normalizedType = strtolower(trim($type));
        $normalizedChannel = strtolower(trim((string) $channel));

        if (
            $normalizedType === self::POLICY_GENERIC
            && isset(self::SPECIAL_GENERIC_CHANNELS[$normalizedChannel])
        ) {
            return self::SPECIAL_GENERIC_CHANNELS[$normalizedChannel];
        }

        return array_key_exists($normalizedType, self::getDefaultPolicy())
            ? $normalizedType
            : self::POLICY_GENERIC;
    }

    public function getSpecialGenericChannels(): array
    {
        return self::SPECIAL_GENERIC_CHANNELS;
    }

    public static function getDefaultPolicy(): array
    {
        return [
            self::POLICY_ENTITY => [
                'enabled' => true,
                'retentionDays' => null,
            ],
            self::POLICY_GENERIC => [
                'enabled' => true,
                'retentionDays' => null,
            ],
            self::POLICY_OPERATION_PATTERNS => [
                'enabled' => true,
                'retentionDays' => null,
            ],
            self::POLICY_FRONTEND_DEBUG => [
                'enabled' => true,
                'retentionDays' => null,
            ],
            self::POLICY_BACKEND_ERROR => [
                'enabled' => true,
                'retentionDays' => null,
            ],
        ];
    }

    private function resolvePolicyEntry(string $type, ?string $channel = null): array
    {
        $policyKey = $this->resolvePolicyKey($type, $channel);
        $policy = $this->getLogPolicy();

        return $policy[$policyKey] ?? self::getDefaultPolicy()[self::POLICY_GENERIC];
    }

    private function getMainCompanyConfig(string $key): mixed
    {
        $mainCompany = $this->getMainCompany();
        if (!$mainCompany instanceof People) {
            return null;
        }

        return $this->configService->getConfig($mainCompany, $key, true);
    }

    private function normalizePolicy(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach (self::getDefaultPolicy() as $policyKey => $defaults) {
            $policyConfig = $value[$policyKey] ?? null;
            if (!is_array($policyConfig)) {
                continue;
            }

            $normalized[$policyKey] = [
                'enabled' => $this->normalizeBoolean(
                    $policyConfig['enabled'] ?? $defaults['enabled']
                ),
                'retentionDays' => $this->normalizeRetentionDays(
                    $policyConfig['retentionDays'] ?? $defaults['retentionDays']
                ),
            ];
        }

        return $normalized;
    }

    private function normalizeRecipients(mixed $value): array
    {
        $emails = [];

        if (is_array($value)) {
            $emails = $value;
        } elseif (is_string($value)) {
            $emails = preg_split('/[\r\n,;]+/', $value) ?: [];
        }

        return array_values(array_unique(array_filter(array_map(
            function (mixed $email): ?string {
                $normalizedEmail = strtolower(trim((string) $email));
                if ($normalizedEmail === '') {
                    return null;
                }

                return filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)
                    ? $normalizedEmail
                    : null;
            },
            $emails
        ))));
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $normalizedValue = strtolower(trim((string) $value));

        return in_array($normalizedValue, ['1', 'true', 'yes', 'on'], true);
    }

    private function normalizeRetentionDays(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $normalizedValue = (int) $value;

        return $normalizedValue > 0 ? $normalizedValue : null;
    }
}
