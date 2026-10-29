<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'commandes')]
class Commandes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Clients::class, inversedBy: 'commandes')]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Clients $client = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateCommande = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $statut = 'En attente';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $total;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $fraisLivraison = '0.00';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adresseLivraison = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $modePaiement = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true, unique: true)]
    private ?string $numeroCommande = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $codePromoUtilise = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $numeroSuivi = null;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: DetailsCommande::class, cascade: ['persist', 'remove'])]
    private Collection $detailsCommandes;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: Paiements::class, cascade: ['persist', 'remove'])]
    private Collection $paiements;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: Impressions::class, cascade: ['persist', 'remove'])]
    private Collection $impressions;

    public function __construct()
    {
        $this->detailsCommandes = new ArrayCollection();
        $this->paiements = new ArrayCollection();
        $this->impressions = new ArrayCollection();
        $this->dateCommande = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?Clients
    {
        return $this->client;
    }

    public function setClient(?Clients $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getDateCommande(): ?\DateTimeInterface
    {
        return $this->dateCommande;
    }

    public function setDateCommande(\DateTimeInterface $dateCommande): self
    {
        $this->dateCommande = $dateCommande;
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

    public function getTotal(): string
    {
        return $this->total;
    }

    public function setTotal(string $total): self
    {
        $this->total = $total;
        return $this;
    }

    public function getFraisLivraison(): string
    {
        return $this->fraisLivraison;
    }

    public function setFraisLivraison(string $fraisLivraison): self
    {
        $this->fraisLivraison = $fraisLivraison;
        return $this;
    }

    public function getAdresseLivraison(): ?string
    {
        return $this->adresseLivraison;
    }

    public function setAdresseLivraison(?string $adresseLivraison): self
    {
        $this->adresseLivraison = $adresseLivraison;
        return $this;
    }

    public function getModePaiement(): ?string
    {
        return $this->modePaiement;
    }

    public function setModePaiement(?string $modePaiement): self
    {
        $this->modePaiement = $modePaiement;
        return $this;
    }

    public function getNumeroCommande(): ?string
    {
        return $this->numeroCommande;
    }

    public function setNumeroCommande(?string $numeroCommande): self
    {
        $this->numeroCommande = $numeroCommande;
        return $this;
    }

    public function getCodePromoUtilise(): ?string
    {
        return $this->codePromoUtilise;
    }

    public function setCodePromoUtilise(?string $code): self
    {
        $this->codePromoUtilise = $code;
        return $this;
    }

    public function getNumeroSuivi(): ?string
    {
        return $this->numeroSuivi;
    }

    public function setNumeroSuivi(?string $numeroSuivi): self
    {
        $this->numeroSuivi = $numeroSuivi;
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
            $detailsCommande->setCommande($this);
        }
        return $this;
    }

    public function removeDetailsCommande(DetailsCommande $detailsCommande): self
    {
        if ($this->detailsCommandes->removeElement($detailsCommande)) {
            if ($detailsCommande->getCommande() === $this) {
                $detailsCommande->setCommande(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Paiements>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiements $paiement): self
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setCommande($this);
        }
        return $this;
    }

    public function removePaiement(Paiements $paiement): self
    {
        if ($this->paiements->removeElement($paiement)) {
            if ($paiement->getCommande() === $this) {
                $paiement->setCommande(null);
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
            $impression->setCommande($this);
        }
        return $this;
    }

    public function removeImpression(Impressions $impression): self
    {
        if ($this->impressions->removeElement($impression)) {
            if ($impression->getCommande() === $this) {
                $impression->setCommande(null);
            }
        }
        return $this;
    }
}
