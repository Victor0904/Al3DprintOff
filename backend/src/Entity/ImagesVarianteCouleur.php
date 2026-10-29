<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'images_variante_couleur')]
class ImagesVarianteCouleur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: VarianteCouleur::class, inversedBy: 'imagesVariante')]
    #[ORM\JoinColumn(name: 'variante_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?VarianteCouleur $variante = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $url;

    #[ORM\Column(type: 'integer')]
    private int $ordre = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVariante(): ?VarianteCouleur
    {
        return $this->variante;
    }

    public function setVariante(?VarianteCouleur $variante): self
    {
        $this->variante = $variante;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): self
    {
        $this->ordre = $ordre;
        return $this;
    }
}
