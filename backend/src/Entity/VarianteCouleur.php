<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'variantes_couleur')]
class VarianteCouleur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Produits::class, inversedBy: 'variantesCouleur')]
    #[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Produits $produit = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $nomCouleur;

    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    private ?string $codeCouleur = null; // Code hexadécimal de la couleur (ex: #FF5733)

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $reference = null; // Référence unique pour cette variante

    #[ORM\Column(type: 'integer')]
    private int $stock = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $actif = true;

    #[ORM\Column(type: 'integer')]
    private int $ordre = 0; // Ordre d'affichage

    #[ORM\OneToMany(mappedBy: 'variante', targetEntity: ImagesVarianteCouleur::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $imagesVariante;

    public function __construct()
    {
        $this->imagesVariante = new ArrayCollection();
    }

    public function getImagesVariante(): Collection
    {
        return $this->imagesVariante;
    }

    public function addImagesVariante(ImagesVarianteCouleur $image): self
    {
        if (!$this->imagesVariante->contains($image)) {
            $this->imagesVariante->add($image);
            $image->setVariante($this);
        }
        return $this;
    }

    public function removeImagesVariante(ImagesVarianteCouleur $image): self
    {
        $this->imagesVariante->removeElement($image);
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduit(): ?Produits
    {
        return $this->produit;
    }

    public function setProduit(?Produits $produit): self
    {
        $this->produit = $produit;
        return $this;
    }

    public function getNomCouleur(): string
    {
        return $this->nomCouleur;
    }

    public function setNomCouleur(string $nomCouleur): self
    {
        $this->nomCouleur = $nomCouleur;
        return $this;
    }

    public function getCodeCouleur(): ?string
    {
        return $this->codeCouleur;
    }

    public function setCodeCouleur(?string $codeCouleur): self
    {
        $this->codeCouleur = $codeCouleur;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): self
    {
        $this->stock = $stock;
        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): self
    {
        $this->actif = $actif;
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

