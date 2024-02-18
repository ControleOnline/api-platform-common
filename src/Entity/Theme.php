<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\ORM\Mapping as ORM;
use stdClass;
use Symfony\Component\Serializer\Annotation\Groups;
use ControleOnline\Controller\GetThemeColorsAction;

/**
 * theme
 *
 * @ORM\EntityListeners ({App\Listener\LogListener::class})
 * @ORM\Table (name="theme")
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\ThemeRepository")
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')'),
        new GetCollection(
            security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
            uriTemplate: '/themes/colors.css',
            controller: GetThemeColorsAction::class
        ),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']],
    normalizationContext: ['groups' => ['theme_read']],
    denormalizationContext: ['groups' => ['theme_write']]
)]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['theme' => 'ASC'])]
#[ApiFilter(
    filterClass: SearchFilter::class,
    properties: [
        'theme' => 'partial',
        'state.uf' => 'exact'
    ]
)]
class Theme
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"theme_read"})
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="theme", type="string", length=80, nullable=false)
     * @Groups({"theme_read"})
     */
    private $theme;
    /**
     * @var string
     *
     * @ORM\Column(name="background", type="integer", nullable=true)
     * @Groups({"theme_read"})

     */
    private $background;
    /**
     * @var string
     *
     * @ORM\Column(name="colors", type="json", nullable=false)
     * @Groups({"theme_read"})
     */
    private $colors;

    public function __construct()
    {
    }
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Set theme
     *
     * @param string $theme
     * @return Theme
     */
    public function settheme($theme)
    {
        $this->theme = $theme;
        return $this;
    }
    /**
     * Get theme
     *
     * @return string
     */
    public function gettheme()
    {
        return strtoupper($this->theme);
    }

    /**
     * Get the value of background
     */
    public function getBackground()
    {
        return $this->background;
    }

    /**
     * Set the value of background
     */
    public function setBackground($background): self
    {
        $this->background = $background;

        return $this;
    }
    /**
     * Get otherInformations
     *
     * @return stdClass
     */
    public function getColors($decode = false)
    {
        return $decode ? (object) json_decode((is_array($this->colors) ? json_encode($this->colors) : $this->colors)) : $this->colors;
    }


    /**
     * Set the value of colors
     */
    public function setColors(string $colors): self
    {
        $this->colors = json_encode($colors);

        return $this;
    }



    /**
     * Set comments
     *
     * @param string $colors
     * @return Theme
     */
    public function addColors($key, $value)
    {
        $colors = $this->getColors(true);
        $colors->$key = $value;
        $this->colors = json_encode($colors);
        return $this;
    }
}
