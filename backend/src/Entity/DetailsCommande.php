<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'details_commande')]
class DetailsCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Commandes::class, inversedBy: 'detailsCommandes')]
    #[ORM\JoinColumn(name: 'commande_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Commandes $commande = null;

    #[ORM\ManyToOne(targetEntity: Produits::class, inversedBy: 'detailsCommandes')]
    #[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Produits $produit = null;

    #[ORM\Column(type: 'integer')]
    private int $quantite;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $prixUnitaire;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $personnalisation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): ?Commandes
    {
        return $this->commande;
    }

    public function setCommande(?Commandes $commande): self
    {
        $this->commande = $commande;
        return $this;
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

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getPrixUnitaire(): string
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(string $prixUnitaire): self
    {
        $this->prixUnitaire = $prixUnitaire;
        return $this;
    }

    public function getPersonnalisation(): ?array
    {
        return $this->personnalisation;
    }

    public function setPersonnalisation(?array $personnalisation): self
    {
        $this->personnalisation = $personnalisation;
        return $this;
    }
}
