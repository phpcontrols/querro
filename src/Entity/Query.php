<?php

namespace App\Entity;

use App\Repository\QueryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QueryRepository::class)]
class Query
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $user_id;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', length: 255)]
    private $db;

    #[ORM\Column(type: 'text')]
    private $query;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $share_key;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private $share_pass;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $date_created;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $date_modified;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDb(): ?string
    {
        return $this->db;
    }

    public function setDb(string $db): self
    {
        $this->db = $db;

        return $this;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(string $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function getShareKey(): ?string
    {
        return $this->share_key;
    }

    public function setShareKey(?string $share_key): self
    {
        $this->share_key = $share_key;

        return $this;
    }

    public function getSharePass(): ?string
    {
        return $this->share_pass;
    }

    public function setSharePass(?string $share_pass): self
    {
        $this->share_pass = $share_pass;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->date_created;
    }

    public function setDateCreated(): self
    {
        $date = new \DateTime();
        $this->date_created = $date->getTimestamp();
        
        return $this;
    }

    public function getDateModified(): ?\DateTimeInterface
    {
        return $this->date_modified;
    }

    public function setDateModified(): self
    {
        $date = new \DateTime();
        $this->date_modified = $date->getTimestamp();
        
        return $this;
    }
}
