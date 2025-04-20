<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ControleOnline\Controller\GetThemeColorsAction;
use ControleOnline\Listener\LogListener;
use ControleOnline\Repository\ThemeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(
            security: 'is_granted(\'PUBLIC_ACCESS\')',
            uriTemplate: '/themes-colors.css',
            controller: GetThemeColorsAction::class
        ),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv'], 'css' => ['text/css']],
    normalizationContext: ['groups' => ['theme:read']],
    denormalizationContext: ['groups' => ['theme:write']]
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['theme' => 'ASC'])]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'theme' => 'partial',
        'state.uf' => 'exact'
    ]
)]
#[ORM\Table(name: 'theme')]
#[ORM\EntityListeners([LogListener::class])]
#[ORM\Entity(repositoryClass: ThemeRepository::class)]
class Theme
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Groups(['theme:read'])]
    private int $id = 0;

    #[ORM\Column(name: 'theme', type: 'string', length: 80, nullable: false)]
    #[Groups(['theme:read'])]
    private string $theme;

    #[ORM\Column(name: 'background', type: 'integer', nullable: true)]
    #[Groups(['theme:read'])]
    private ?int $background = null;

    #[ORM\Column(name: 'colors', type: 'json', nullable: false)]
    #[Groups(['theme:read'])]
    private array $colors;

    public function getId(): int
    {
        return $this->id;
    }

    public function setTheme(string $theme): self
    {
        $this->theme = $theme;
        return $this;
    }

    public function getTheme(): string
    {
        return strtoupper($this->theme);
    }

    public function getBackground(): ?int
    {
        return $this->background;
    }

    public function setBackground(?int $background): self
    {
        $this->background = $background;
        return $this;
    }

    public function getColors(bool $decode = false): mixed
    {
        return $decode ? (object) json_decode(json_encode($this->colors)) : $this->colors;
    }

    public function setColors(array $colors): self
    {
        $this->colors = $colors;
        return $this;
    }

    public function addColors(string $key, mixed $value): self
    {
        $colors = $this->getColors(true);
        $colors->$key = $value;
        $this->colors = (array) $colors;
        return $this;
    }
}