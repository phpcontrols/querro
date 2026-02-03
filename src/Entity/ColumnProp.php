<?php

namespace App\Entity;

use App\Repository\ColumnPropRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ColumnPropRepository::class)]
class ColumnProp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $query_id;

    #[ORM\Column(type: 'string', length: 255)]
    private $db;

    #[ORM\Column(type: 'string', length: 255)]
    private $db_table;

    #[ORM\Column(type: 'string', length: 2, nullable: true)]
    private $metatype;

    #[ORM\Column(type: 'string', length: 255)]
    private $col_name;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private $hidden;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private $readonly;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private $required;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private $wysiwyg;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $title;

    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private $align;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $width;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $linkto;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $edittype;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $conditional_format;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $customrule;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $default_value;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $format;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $hyperlink;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $property;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $img;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $file_upload;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQueryId(): ?int
    {
        return $this->query_id;
    }

    public function setQueryId(?int $query_id): self
    {
        $this->query_id = $query_id;

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

    public function getDbTable(): ?string
    {
        return $this->db_table;
    }

    public function setDbTable(string $db_table): self
    {
        $this->db_table = $db_table;

        return $this;
    }

    public function getMetatype(): ?string
    {
        return $this->metatype;
    }

    public function setMetatype(?string $metatype): self
    {
        $this->metatype = $metatype;

        return $this;
    }

    public function getColName(): ?string
    {
        return $this->col_name;
    }

    public function setColName(string $col_name): self
    {
        $this->col_name = $col_name;

        return $this;
    }

    public function getHidden(): ?int
    {
        return $this->hidden;
    }

    public function setHidden(?int $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function getReadonly(): ?int
    {
        return $this->readonly;
    }

    public function setReadonly(?int $readonly): self
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function getRequired(): ?int
    {
        return $this->required;
    }

    public function setRequired(?int $required): self
    {
        $this->required = $required;

        return $this;
    }

    public function getWysiwyg(): ?int
    {
        return $this->wysiwyg;
    }

    public function setWysiwyg(?int $wysiwyg): self
    {
        $this->wysiwyg = $wysiwyg;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getAlign(): ?string
    {
        return $this->align;
    }

    public function setAlign(?string $align): self
    {
        $this->align = $align;

        return $this;
    }

    public function getWidth(): ?string
    {
        return $this->width;
    }

    public function setWidth(?string $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function getLinkto(): ?string
    {
        return $this->linkto;
    }

    public function setLinkto(?string $linkto): self
    {
        $this->linkto = $linkto;

        return $this;
    }

    public function getEdittype(): ?string
    {
        return $this->edittype;
    }

    public function setEdittype(?string $edittype): self
    {
        $this->edittype = $edittype;

        return $this;
    }

    public function getConditionalFormat(): ?string
    {
        return $this->conditional_format;
    }

    public function setConditionalFormat(?string $conditional_format): self
    {
        $this->conditional_format = $conditional_format;

        return $this;
    }

    public function getCustomrule(): ?string
    {
        return $this->customrule;
    }

    public function setCustomrule(?string $customrule): self
    {
        $this->customrule = $customrule;

        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->default_value;
    }

    public function setDefaultValue(?string $default_value): self
    {
        $this->default_value = $default_value;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getHyperlink(): ?string
    {
        return $this->hyperlink;
    }

    public function setHyperlink(?string $hyperlink): self
    {
        $this->hyperlink = $hyperlink;

        return $this;
    }

    public function getProperty(): ?string
    {
        return $this->property;
    }

    public function setProperty(?string $property): self
    {
        $this->property = $property;

        return $this;
    }

    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(?string $img): self
    {
        $this->img = $img;

        return $this;
    }

    public function getFileUpload(): ?string
    {
        return $this->file_upload;
    }

    public function setFileUpload(?string $file_upload): self
    {
        $this->file_upload = $file_upload;

        return $this;
    }
}
