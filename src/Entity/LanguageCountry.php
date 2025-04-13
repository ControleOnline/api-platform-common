<?php

namespace ControleOnline\Entity; 
use ControleOnline\Listener\LogListener;

use Doctrine\ORM\Mapping as ORM;

/**
 * LanguageCountry
 */
#[ORM\Table(name: 'language_country')]
#[ORM\Index(name: 'country_id', columns: ['country_id'])]
#[ORM\Index(name: 'IDX_F7BE1E3282F1BAF4', columns: ['language_id'])]
#[ORM\UniqueConstraint(name: 'language_id', columns: ['language_id', 'country_id'])]
#[ORM\Entity]
#[ORM\EntityListeners([LogListener::class])]
class LanguageCountry
{
    /**
     * @var integer
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var Language
     */
    #[ORM\JoinColumn(name: 'language_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Language::class)]
    private $language;

    /**
     * @var Country
     */
    #[ORM\JoinColumn(name: 'country_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: Country::class, inversedBy: 'languageCountry')]
    private $country;

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
     * Set language
     *
     * @param Language $language
     * @return LanguageCountry
     */
    public function setLanguage(Language $language = null)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set country
     *
     * @param Country $country
     * @return LanguageCountry
     */
    public function setCountry(Country $country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return Country
     */
    public function getCountry()
    {
        return $this->country;
    }
}
