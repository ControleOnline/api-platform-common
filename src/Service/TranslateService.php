<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Language;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Translate;
use ControleOnline\Entity\User;
use ControleOnline\Repository\LanguageRepository;
use ControleOnline\Repository\TranslateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface as Security;

class TranslateService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private Security $security,
        private PeopleRoleService $peopleRoleService
    ) {}

    public function createFromContent(?string $content): Translate
    {
        return $this->persistFromContent($content);
    }

    public function createFromPayload(array $payload): Translate
    {
        return $this->persistFromPayload($payload);
    }

    public function persistFromContent(?string $content): Translate
    {
        return $this->persistFromPayload($this->decodePayload($content));
    }

    public function persistFromPayload(array $payload): Translate
    {
        if ($payload === []) {
            throw new BadRequestHttpException('Invalid JSON');
        }

        $intentional = $this->normalizeBoolean($payload['intentional'] ?? false);
        if (!$intentional) {
            throw new BadRequestHttpException('Intentional translation save required');
        }

        $requiredFields = [];
        foreach (['key', 'language', 'people', 'store', 'type', 'translate'] as $field) {
            $requiredFields[$field] = $this->requireStringPayloadField($payload, $field);
        }

        $people = $this->resolvePeople($requiredFields['people']);
        if (!$people instanceof People) {
            throw new BadRequestHttpException('People not found');
        }
        $this->assertCompanyAccess($people);

        $language = $this->resolveLanguage($requiredFields['language']);
        if (!$language instanceof Language) {
            throw new BadRequestHttpException('Language not found');
        }

        $existing = $this->manager->getRepository(Translate::class)->findOneBy([
            'key' => $requiredFields['key'],
            'language' => $language,
            'people' => $people,
            'store' => $requiredFields['store'],
            'type' => $requiredFields['type'],
        ]);

        if ($existing instanceof Translate) {
            $existing->setTranslate($requiredFields['translate']);
            $existing->setRevised(true);
            $this->manager->flush();

            return $existing;
        }

        $translate = new Translate();
        $translate->setKey($requiredFields['key']);
        $translate->setLanguage($language);
        $translate->setPeople($people);
        $translate->setStore($requiredFields['store']);
        $translate->setType($requiredFields['type']);
        $translate->setTranslate($requiredFields['translate']);
        $translate->setRevised($this->normalizeBoolean($payload['revised'] ?? true) || $intentional);

        $this->manager->persist($translate);
        $this->manager->flush();

        return $translate;
    }

    public function buildOverview(array $filters): array
    {
        if (!isset($filters['people'])) {
            throw new BadRequestHttpException("Field 'people' is required");
        }

        $selectedCompany = $this->resolvePeople($filters['people']);
        if (!$selectedCompany instanceof People) {
            throw new BadRequestHttpException('People not found');
        }
        $this->assertCompanyAccess($selectedCompany);

        $languageFilter = $filters['language.language'] ?? $filters['language_language'] ?? $filters['language'] ?? null;
        $language = $this->resolveLanguage($languageFilter);
        if (!$language instanceof Language) {
            throw new BadRequestHttpException('Language not found');
        }

        /** @var TranslateRepository $repository */
        $repository = $this->manager->getRepository(Translate::class);
        $baseFilters = [
            'store' => trim((string) ($filters['store'] ?? '')),
            'type' => trim((string) ($filters['type'] ?? '')),
        ];

        $companyTranslations = $repository->findForOverview($selectedCompany, $language, $baseFilters);
        $mainCompany = $this->peopleRoleService->getMainCompany();
        $fallbackTranslations = $selectedCompany->getId() === $mainCompany->getId()
            ? []
            : $repository->findForOverview($mainCompany, $language, $baseFilters);

        $itemsByKey = [];
        foreach ($fallbackTranslations as $translation) {
            $itemsByKey[$this->getOverviewKey($translation)] = [
                'company' => null,
                'fallback' => $translation,
            ];
        }

        foreach ($companyTranslations as $translation) {
            $key = $this->getOverviewKey($translation);
            if (!isset($itemsByKey[$key])) {
                $itemsByKey[$key] = [
                    'company' => null,
                    'fallback' => null,
                ];
            }
            $itemsByKey[$key]['company'] = $translation;
        }

        $items = array_map(
            fn (array $row) => $this->formatOverviewItem(
                $row['company'] ?? null,
                $row['fallback'] ?? null,
                $selectedCompany,
                $mainCompany,
                $language
            ),
            array_values($itemsByKey)
        );

        $items = $this->filterOverviewItems(
            $items,
            trim((string) ($filters['search'] ?? ''))
        );

        $summaryItems = $items;
        $pendingReview = $this->resolveNullableBoolean($filters['pendingReview'] ?? null);
        if ($pendingReview !== null) {
            $items = array_values(array_filter(
                $items,
                fn (array $item) => $item['pendingReview'] === $pendingReview
            ));
        }

        return [
            'items' => array_values($items),
            'summary' => $this->buildOverviewSummary($summaryItems, $selectedCompany, $mainCompany, $language),
        ];
    }

    private function decodePayload(?string $content): array
    {
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeReferenceId(mixed $reference): string
    {
        return preg_replace('/\D/', '', (string) $reference);
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'sim'], true);
        }

        return false;
    }

    private function requireStringPayloadField(array $payload, string $field): string
    {
        if (!array_key_exists($field, $payload)) {
            throw new BadRequestHttpException("Field '{$field}' is required");
        }

        $value = trim((string) $payload[$field]);
        if ($value === '') {
            throw new BadRequestHttpException("Field '{$field}' is required");
        }

        return $value;
    }

    private function resolveNullableBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->normalizeBoolean($value);
    }

    private function resolvePeople(mixed $reference): ?People
    {
        $id = $this->normalizeReferenceId($reference);
        if ($id === '') {
            return null;
        }

        return $this->manager->getRepository(People::class)->find($id);
    }

    private function resolveLanguage(mixed $reference): ?Language
    {
        if ($reference === null || $reference === '') {
            return null;
        }

        if (is_numeric($reference) || preg_match('/^\/languages\/\d+$/', (string) $reference)) {
            $id = $this->normalizeReferenceId($reference);
            return $id === '' ? null : $this->manager->getRepository(Language::class)->find($id);
        }

        /** @var LanguageRepository $repository */
        $repository = $this->manager->getRepository(Language::class);

        return $repository->findOneByCode((string) $reference);
    }

    private function assertCompanyAccess(People $company): void
    {
        $token = $this->security->getToken();
        $user = $token?->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $userPeople = $user->getPeople();
        if ($userPeople->getId() === $company->getId()) {
            return;
        }

        $link = $this->manager->getRepository(People::class)->getCompanyPeopleLinks(
            $company,
            $userPeople,
            null,
            1
        );

        if ($link === null) {
            throw new AccessDeniedHttpException('Access denied for company');
        }
    }

    private function getOverviewKey(Translate $translation): string
    {
        return implode('|', [
            $translation->getLanguage()?->getId(),
            $translation->getStore(),
            $translation->getType(),
            $translation->getKey(),
        ]);
    }

    private function formatOverviewItem(
        ?Translate $companyTranslation,
        ?Translate $fallbackTranslation,
        People $selectedCompany,
        People $mainCompany,
        Language $language
    ): array {
        $effectiveTranslation = $companyTranslation instanceof Translate
            ? $companyTranslation
            : $fallbackTranslation;

        if (!$effectiveTranslation instanceof Translate) {
            throw new BadRequestHttpException('Translate not found');
        }

        return [
            'rowId' => $companyTranslation instanceof Translate
                ? 'translate-' . $companyTranslation->getId()
                : 'fallback-' . $fallbackTranslation?->getId(),
            'translateId' => $companyTranslation?->getId(),
            'fallbackId' => $fallbackTranslation?->getId(),
            'language' => [
                '@id' => '/languages/' . $language->getId(),
                'id' => $language->getId(),
                'language' => $language->getLanguage(),
            ],
            'people' => [
                '@id' => '/people/' . $selectedCompany->getId(),
                'id' => $selectedCompany->getId(),
                'name' => $selectedCompany->getAlias() ?: $selectedCompany->getName(),
            ],
            'mainCompany' => [
                '@id' => '/people/' . $mainCompany->getId(),
                'id' => $mainCompany->getId(),
                'name' => $mainCompany->getAlias() ?: $mainCompany->getName(),
            ],
            'store' => $effectiveTranslation->getStore(),
            'type' => $effectiveTranslation->getType(),
            'key' => $effectiveTranslation->getKey(),
            'translate' => $effectiveTranslation->getTranslate(),
            'revised' => $effectiveTranslation->isRevised(),
            'pendingReview' => !$effectiveTranslation->isRevised(),
            'hasOverride' => $companyTranslation instanceof Translate,
            'source' => $companyTranslation instanceof Translate ? 'company' : 'main_company',
            'companyTranslate' => $companyTranslation?->getTranslate(),
            'companyRevised' => $companyTranslation?->isRevised(),
            'mainTranslate' => $fallbackTranslation?->getTranslate(),
            'mainRevised' => $fallbackTranslation?->isRevised(),
        ];
    }

    private function filterOverviewItems(array $items, string $search): array
    {
        if ($search === '') {
            return array_values($items);
        }

        $needle = mb_strtolower($search);

        return array_values(array_filter($items, function (array $item) use ($needle) {
            $haystack = implode(' ', array_filter([
                $item['store'] ?? '',
                $item['type'] ?? '',
                $item['key'] ?? '',
                $item['translate'] ?? '',
                $item['companyTranslate'] ?? '',
                $item['mainTranslate'] ?? '',
            ]));

            return mb_strtolower($haystack) !== ''
                && str_contains(mb_strtolower($haystack), $needle);
        }));
    }

    private function buildOverviewSummary(
        array $items,
        People $selectedCompany,
        People $mainCompany,
        Language $language
    ): array {
        return [
            'total' => count($items),
            'pendingReview' => count(array_filter($items, fn (array $item) => $item['pendingReview'])),
            'reviewed' => count(array_filter($items, fn (array $item) => !$item['pendingReview'])),
            'overrides' => count(array_filter($items, fn (array $item) => $item['hasOverride'])),
            'fallbacks' => count(array_filter($items, fn (array $item) => !$item['hasOverride'])),
            'selectedCompany' => [
                'id' => $selectedCompany->getId(),
                'name' => $selectedCompany->getAlias() ?: $selectedCompany->getName(),
            ],
            'mainCompany' => [
                'id' => $mainCompany->getId(),
                'name' => $mainCompany->getAlias() ?: $mainCompany->getName(),
            ],
            'language' => [
                'id' => $language->getId(),
                'language' => $language->getLanguage(),
            ],
        ];
    }
}
