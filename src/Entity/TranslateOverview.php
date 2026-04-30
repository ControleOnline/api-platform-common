<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ControleOnline\State\TranslateOverviewProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/translates/overview',
            provider: TranslateOverviewProvider::class,
            security: "is_granted('ROLE_HUMAN')",
            paginationEnabled: false,
        ),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['translate_overview:read']]
)]
class TranslateOverview
{
    #[ApiProperty(identifier: true)]
    #[Groups(['translate_overview:read'])]
    private string $rowId = '';

    #[Groups(['translate_overview:read'])]
    private ?int $translateId = null;

    #[Groups(['translate_overview:read'])]
    private ?int $fallbackId = null;

    #[Groups(['translate_overview:read'])]
    private array $language = [];

    #[Groups(['translate_overview:read'])]
    private array $people = [];

    #[Groups(['translate_overview:read'])]
    private array $mainCompany = [];

    #[Groups(['translate_overview:read'])]
    private string $store = '';

    #[Groups(['translate_overview:read'])]
    private string $type = '';

    #[Groups(['translate_overview:read'])]
    private string $key = '';

    #[Groups(['translate_overview:read'])]
    private string $translate = '';

    #[Groups(['translate_overview:read'])]
    private bool $revised = false;

    #[Groups(['translate_overview:read'])]
    private bool $pendingReview = false;

    #[Groups(['translate_overview:read'])]
    private bool $hasOverride = false;

    #[Groups(['translate_overview:read'])]
    private string $source = 'company';

    #[Groups(['translate_overview:read'])]
    private ?string $companyTranslate = null;

    #[Groups(['translate_overview:read'])]
    private ?bool $companyRevised = null;

    #[Groups(['translate_overview:read'])]
    private ?string $mainTranslate = null;

    #[Groups(['translate_overview:read'])]
    private ?bool $mainRevised = null;

    public static function fromArray(array $data): self
    {
        $overview = new self();
        $overview->rowId = (string) ($data['rowId'] ?? '');
        $overview->translateId = isset($data['translateId']) ? (int) $data['translateId'] : null;
        $overview->fallbackId = isset($data['fallbackId']) ? (int) $data['fallbackId'] : null;
        $overview->language = is_array($data['language'] ?? null) ? $data['language'] : [];
        $overview->people = is_array($data['people'] ?? null) ? $data['people'] : [];
        $overview->mainCompany = is_array($data['mainCompany'] ?? null) ? $data['mainCompany'] : [];
        $overview->store = (string) ($data['store'] ?? '');
        $overview->type = (string) ($data['type'] ?? '');
        $overview->key = (string) ($data['key'] ?? '');
        $overview->translate = (string) ($data['translate'] ?? '');
        $overview->revised = (bool) ($data['revised'] ?? false);
        $overview->pendingReview = (bool) ($data['pendingReview'] ?? false);
        $overview->hasOverride = (bool) ($data['hasOverride'] ?? false);
        $overview->source = (string) ($data['source'] ?? 'company');
        $overview->companyTranslate = isset($data['companyTranslate']) ? (string) $data['companyTranslate'] : null;
        $overview->companyRevised = isset($data['companyRevised']) ? (bool) $data['companyRevised'] : null;
        $overview->mainTranslate = isset($data['mainTranslate']) ? (string) $data['mainTranslate'] : null;
        $overview->mainRevised = isset($data['mainRevised']) ? (bool) $data['mainRevised'] : null;

        return $overview;
    }

    public function getRowId(): string
    {
        return $this->rowId;
    }

    public function getTranslateId(): ?int
    {
        return $this->translateId;
    }

    public function getFallbackId(): ?int
    {
        return $this->fallbackId;
    }

    public function getLanguage(): array
    {
        return $this->language;
    }

    public function getPeople(): array
    {
        return $this->people;
    }

    public function getMainCompany(): array
    {
        return $this->mainCompany;
    }

    public function getStore(): string
    {
        return $this->store;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTranslate(): string
    {
        return $this->translate;
    }

    public function isRevised(): bool
    {
        return $this->revised;
    }

    public function isPendingReview(): bool
    {
        return $this->pendingReview;
    }

    public function hasOverride(): bool
    {
        return $this->hasOverride;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getCompanyTranslate(): ?string
    {
        return $this->companyTranslate;
    }

    public function getCompanyRevised(): ?bool
    {
        return $this->companyRevised;
    }

    public function getMainTranslate(): ?string
    {
        return $this->mainTranslate;
    }

    public function getMainRevised(): ?bool
    {
        return $this->mainRevised;
    }
}
