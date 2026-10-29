<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'images_produit_personnalise')]
class ImagesProduitPersonnalise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProduitPersonnalise::class, inversedBy: 'imagesPersonnalise')]
    #[ORM\JoinColumn(name: 'produit_personnalise_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ProduitPersonnalise $produitPersonnalise = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $url;

    #[ORM\Column(type: 'integer')]
    private int $ordre = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduitPersonnalise(): ?ProduitPersonnalise
    {
        return $this->produitPersonnalise;
    }

    public function setProduitPersonnalise(?ProduitPersonnalise $produitPersonnalise): self
    {
        $this->produitPersonnalise = $produitPersonnalise;
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
