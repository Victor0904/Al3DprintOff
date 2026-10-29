<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'produit_personnalise')]
class ProduitPersonnalise
{
    #[ORM\OneToMany(mappedBy: 'produitPersonnalise', targetEntity: ImagesProduitPersonnalise::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['ordre' => 'ASC'])]
    private Collection $imagesPersonnalise;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 150)]
    private string $nom;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $prix;

    #[ORM\Column(type: 'integer')]
    private int $stock = 0;

    #[ORM\ManyToOne(targetEntity: Categories::class)]
    #[ORM\JoinColumn(name: 'categorie_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Categories $categorie = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $imagePrincipale = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateAjout = null;

    #[ORM\Column(type: 'boolean')]
    private bool $actif = true;

    public function __construct()
    {
        $this->dateAjout = new \DateTime();
        $this->imagesPersonnalise = new ArrayCollection();
    }

    public function getImagesPersonnalise(): Collection
    {
        return $this->imagesPersonnalise;
    }

    public function addImagePersonnalise(ImagesProduitPersonnalise $image): self
    {
        if (!$this->imagesPersonnalise->contains($image)) {
            $this->imagesPersonnalise->add($image);
            $image->setProduitPersonnalise($this);
        }
        return $this;
    }

    public function removeImagePersonnalise(ImagesProduitPersonnalise $image): self
    {
        $this->imagesPersonnalise->removeElement($image);
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPrix(): string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): self
    {
        $this->prix = $prix;
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

    public function getCategorie(): ?Categories
    {
        return $this->categorie;
    }

    public function setCategorie(?Categories $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getImagePrincipale(): ?string
    {
        return $this->imagePrincipale;
    }

    public function setImagePrincipale(?string $imagePrincipale): self
    {
        $this->imagePrincipale = $imagePrincipale;
        return $this;
    }

    public function getDateAjout(): ?\DateTimeInterface
    {
        return $this->dateAjout;
    }

    public function setDateAjout(\DateTimeInterface $dateAjout): self
    {
        $this->dateAjout = $dateAjout;
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
}

