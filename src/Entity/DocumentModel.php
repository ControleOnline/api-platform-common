<?php


namespace ControleOnline\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="document_model")
 * @ORM\Entity
 * @ORM\EntityListeners({ControleOnline\Listener\LogListener::class}) 
 */
class DocumentModel
{
    /**
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="document_model", type="string", nullable=false)
     */
    private $document_model;

    /**
     * @ORM\Column(name="content", type="text", nullable=false)
     */
    private $content;

    /**
     * @ORM\Column(name="people_id", type="integer", nullable=true) 
     */
    private $peopleId;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDocumentModel(): string
    {
        return $this->document_model;
    }

    /**
     * @param string $document_model
     * @return DocumentModel
     */
    public function setDocumentModel(string $document_model): DocumentModel
    {
        $this->document_model = $document_model;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return DocumentModel
     */
    public function setContent(string $content): DocumentModel
    {
        $this->content = $content;
        return $this;
    }

    public function getPeopleId(): int
    {
        return $this->peopleId;
    }

    public function setPeopleId(int $peopleId): DocumentModel
    {
        $this->peopleId = $peopleId;
        return $this;
    }
}
