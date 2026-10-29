<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'impressions')]
class Impressions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Commandes::class, inversedBy: 'impressions')]
    #[ORM\JoinColumn(name: 'commande_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Commandes $commande = null;

    #[ORM\ManyToOne(targetEntity: Produits::class, inversedBy: 'impressions')]
    #[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Produits $produit = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $statut = 'à faire';

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $materiau = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $tempsEstime = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

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

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getMateriau(): ?string
    {
        return $this->materiau;
    }

    public function setMateriau(?string $materiau): self
    {
        $this->materiau = $materiau;
        return $this;
    }

    public function getTempsEstime(): ?int
    {
        return $this->tempsEstime;
    }

    public function setTempsEstime(?int $tempsEstime): self
    {
        $this->tempsEstime = $tempsEstime;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }
}
