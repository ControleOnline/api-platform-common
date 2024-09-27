<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Controller\GetFileDataAction;
use ControleOnline\Controller\FileUploadController;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * File
 *
 * @ORM\EntityListeners ({ControleOnline\Listener\LogListener::class})
 * @ORM\Entity (repositoryClass="ControleOnline\Repository\FileRepository")
 * @ORM\Table (name="files", uniqueConstraints={@ORM\UniqueConstraint (name="url", columns={"url"}), @ORM\UniqueConstraint(name="path", columns={"path"})})
 */
#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Get(
            security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')',
            uriTemplate: '/files/download/{id}',
            controller: GetFileDataAction::class
        ),
        new Delete(security: 'is_granted(\'ROLE_CLIENT\')'),
        new Post(
            security: 'is_granted(\'ROLE_CLIENT\')',
            uriTemplate: '/files/upload',
            controller: FileUploadController::class,
            deserialize: false
        ),
        new GetCollection(security: 'is_granted(\'ROLE_CLIENT\')')
    ],

    normalizationContext: ['groups' => ['file_read']],
    denormalizationContext: ['groups' => ['file_write']]
)]
class File
{
    /**
     *
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Groups({"file_read","people_read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Groups({"file_read","people_read"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['file_type' => 'exact'])]
    private $file_type;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Groups({"file_read","people_read"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['file_name' => 'exact'])]
    private $file_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Groups({"file_read","people_read"})
     * @Assert\NotBlank
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['extension' => 'exact'])]
    private $extension;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $content;


    /**
     * @var \ControleOnline\Entity\People
     *
     * @ORM\ManyToOne(targetEntity="ControleOnline\Entity\People")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="people_id", referencedColumnName="id")
     * })
     * @Groups({"file_read"})
     */
    #[ApiFilter(filterClass: SearchFilter::class, properties: ['people' => 'exact'])]

    private $people;

    public function __construct()
    {
        $this->people = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }



    /**
     * Get the value of file_type
     */
    public function getFileType()
    {
        return $this->file_type;
    }

    /**
     * Set the value of file_type
     */
    public function setFileType($file_type): self
    {
        $this->file_type = $file_type;

        return $this;
    }

    /**
     * Get the value of content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set the value of content
     */
    public function setContent($content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get the value of people
     */
    public function getPeople()
    {
        return $this->people;
    }

    /**
     * Set the value of people
     */
    public function setPeople($people): self
    {
        $this->people = $people;

        return $this;
    }

    /**
     * Get the value of extension
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Set the value of extension
     */
    public function setExtension($extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * Get the value of file_name
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * Set the value of file_name
     */
    public function setFileName($file_name): self
    {
        $this->file_name = $file_name;

        return $this;
    }
}
