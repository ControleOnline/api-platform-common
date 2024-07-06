<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use App\Controller\GetFileDataAction;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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
     * @Groups({"product_file_read","lesson_upload_file:post", "people_read", "task_interaction_read","display_read"})
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Groups({"product_file_read","people_read", "lesson:read", "task_interaction_read","display_read"})
     */
    private $url;
    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Groups({"lesson_upload_file:post", "lesson:read"})
     */
    private $file_type;
    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $content;


    /**
     * @ORM\OneToMany(targetEntity="ControleOnline\Entity\People", mappedBy="file")
     */
    private $people;
    public function __construct()
    {
        $this->people = new \Doctrine\Common\Collections\ArrayCollection();
    }
    public function getId()
    {
        return $this->id;
    }
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }
    public function getUrl()
    {
        return $this->url;
    }

    public function addPeople(People $people)
    {
        $this->people[] = $people;
        return $this;
    }
    public function removePeople(People $people)
    {
        $this->people->removeElement($people);
    }
    public function getPeople()
    {
        return $this->people;
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
}
