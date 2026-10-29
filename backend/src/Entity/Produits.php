<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'produits')]
class Produits
{
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

    #[ORM\ManyToOne(targetEntity: Categories::class, inversedBy: 'produits')]
    #[ORM\JoinColumn(name: 'categorie_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Categories $categorie = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $imagePrincipale = null;

    // #[ORM\Column(type: 'string', length: 255, nullable: true)]
    // private ?string $fichier3d = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateAjout = null;

    #[ORM\Column(type: 'boolean')]
    private bool $actif = true;

    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: OptionsPersonnalisation::class, cascade: ['persist', 'remove'])]
    private Collection $optionsPersonnalisation;

    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: ImagesProduit::class, cascade: ['persist', 'remove'])]
    private Collection $imagesProduit;

    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: DetailsCommande::class)]
    private Collection $detailsCommandes;

    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: AvisClients::class, cascade: ['persist', 'remove'])]
    private Collection $avisClients;

    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: Impressions::class)]
    private Collection $impressions;

    #[ORM\OneToMany(mappedBy: 'produit', targetEntity: VarianteCouleur::class, cascade: ['persist', 'remove'])]
    private Collection $variantesCouleur;

    public function __construct()
    {
        $this->optionsPersonnalisation = new ArrayCollection();
        $this->imagesProduit = new ArrayCollection();
        $this->detailsCommandes = new ArrayCollection();
        $this->avisClients = new ArrayCollection();
        $this->impressions = new ArrayCollection();
        $this->variantesCouleur = new ArrayCollection();
        $this->dateAjout = new \DateTime();
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

    // public function getFichier3d(): ?string
    // {
    //     return $this->fichier3d;
    // }

    // public function setFichier3d(?string $fichier3d): self
    // {
    //     $this->fichier3d = $fichier3d;
    //     return $this;
    // }

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

    /**
     * @return Collection<int, OptionsPersonnalisation>
     */
    public function getOptionsPersonnalisation(): Collection
    {
        return $this->optionsPersonnalisation;
    }

    public function addOptionsPersonnalisation(OptionsPersonnalisation $optionsPersonnalisation): self
    {
        if (!$this->optionsPersonnalisation->contains($optionsPersonnalisation)) {
            $this->optionsPersonnalisation->add($optionsPersonnalisation);
            $optionsPersonnalisation->setProduit($this);
        }
        return $this;
    }

    public function removeOptionsPersonnalisation(OptionsPersonnalisation $optionsPersonnalisation): self
    {
        if ($this->optionsPersonnalisation->removeElement($optionsPersonnalisation)) {
            if ($optionsPersonnalisation->getProduit() === $this) {
                $optionsPersonnalisation->setProduit(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, ImagesProduit>
     */
    public function getImagesProduit(): Collection
    {
        return $this->imagesProduit;
    }

    public function addImagesProduit(ImagesProduit $imagesProduit): self
    {
        if (!$this->imagesProduit->contains($imagesProduit)) {
            $this->imagesProduit->add($imagesProduit);
            $imagesProduit->setProduit($this);
        }
        return $this;
    }

    public function removeImagesProduit(ImagesProduit $imagesProduit): self
    {
        if ($this->imagesProduit->removeElement($imagesProduit)) {
            if ($imagesProduit->getProduit() === $this) {
                $imagesProduit->setProduit(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, DetailsCommande>
     */
    public function getDetailsCommandes(): Collection
    {
        return $this->detailsCommandes;
    }

    public function addDetailsCommande(DetailsCommande $detailsCommande): self
    {
        if (!$this->detailsCommandes->contains($detailsCommande)) {
            $this->detailsCommandes->add($detailsCommande);
            $detailsCommande->setProduit($this);
        }
        return $this;
    }

    public function removeDetailsCommande(DetailsCommande $detailsCommande): self
    {
        if ($this->detailsCommandes->removeElement($detailsCommande)) {
            if ($detailsCommande->getProduit() === $this) {
                $detailsCommande->setProduit(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, AvisClients>
     */
    public function getAvisClients(): Collection
    {
        return $this->avisClients;
    }

    public function addAvisClient(AvisClients $avisClient): self
    {
        if (!$this->avisClients->contains($avisClient)) {
            $this->avisClients->add($avisClient);
            $avisClient->setProduit($this);
        }
        return $this;
    }

    public function removeAvisClient(AvisClients $avisClient): self
    {
        if ($this->avisClients->removeElement($avisClient)) {
            if ($avisClient->getProduit() === $this) {
                $avisClient->setProduit(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Impressions>
     */
    public function getImpressions(): Collection
    {
        return $this->impressions;
    }

    public function addImpression(Impressions $impression): self
    {
        if (!$this->impressions->contains($impression)) {
            $this->impressions->add($impression);
            $impression->setProduit($this);
        }
        return $this;
    }

    public function removeImpression(Impressions $impression): self
    {
        if ($this->impressions->removeElement($impression)) {
            if ($impression->getProduit() === $this) {
                $impression->setProduit(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, VarianteCouleur>
     */
    public function getVariantesCouleur(): Collection
    {
        return $this->variantesCouleur;
    }

    public function addVariantesCouleur(VarianteCouleur $variantesCouleur): self
    {
        if (!$this->variantesCouleur->contains($variantesCouleur)) {
            $this->variantesCouleur->add($variantesCouleur);
            $variantesCouleur->setProduit($this);
        }
        return $this;
    }

    public function removeVariantesCouleur(VarianteCouleur $variantesCouleur): self
    {
        if ($this->variantesCouleur->removeElement($variantesCouleur)) {
            if ($variantesCouleur->getProduit() === $this) {
                $variantesCouleur->setProduit(null);
            }
        }
        return $this;
    }
}
