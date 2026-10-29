<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'clients')]
class Clients
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $nom;

    #[ORM\Column(type: 'string', length: 150, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $motDePasse;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $codePostal = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adresse2 = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ville2 = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $codePostal2 = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adresse3 = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ville3 = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $codePostal3 = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    /** Type de client : particulier (TTC) ou entreprise (HT + TVA 17 %) */
    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'particulier'])]
    private string $typeClient = 'particulier';

    /** Rôle de l'utilisateur : user (par défaut) ou admin */
    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'user'])]
    private string $role = 'user';

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Commandes::class)]
    private Collection $commandes;

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: AvisClients::class, cascade: ['persist', 'remove'])]
    private Collection $avisClients;

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: PanierItem::class, cascade: ['persist', 'remove'])]
    private Collection $panierItems;

    public function __construct()
    {
        $this->commandes = new ArrayCollection();
        $this->avisClients = new ArrayCollection();
        $this->panierItems = new ArrayCollection();
        $this->dateInscription = new \DateTime();
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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getMotDePasse(): string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): self
    {
        $this->motDePasse = $motDePasse;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): self
    {
        $this->codePostal = $codePostal;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeInterface $dateInscription): self
    {
        $this->dateInscription = $dateInscription;
        return $this;
    }

    /**
     * @return Collection<int, Commandes>
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commandes $commande): self
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setClient($this);
        }
        return $this;
    }

    public function removeCommande(Commandes $commande): self
    {
        if ($this->commandes->removeElement($commande)) {
            if ($commande->getClient() === $this) {
                $commande->setClient(null);
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
            $avisClient->setClient($this);
        }
        return $this;
    }

    public function removeAvisClient(AvisClients $avisClient): self
    {
        if ($this->avisClients->removeElement($avisClient)) {
            if ($avisClient->getClient() === $this) {
                $avisClient->setClient(null);
            }
        }
        return $this;
    }


    public function getAdresse2(): ?string
    {
        return $this->adresse2;
    }

    public function setAdresse2(?string $adresse2): self
    {
        $this->adresse2 = $adresse2;
        return $this;
    }

    public function getVille2(): ?string
    {
        return $this->ville2;
    }

    public function setVille2(?string $ville2): self
    {
        $this->ville2 = $ville2;
        return $this;
    }

    public function getCodePostal2(): ?string
    {
        return $this->codePostal2;
    }

    public function setCodePostal2(?string $codePostal2): self
    {
        $this->codePostal2 = $codePostal2;
        return $this;
    }

    public function getAdresse3(): ?string
    {
        return $this->adresse3;
    }

    public function setAdresse3(?string $adresse3): self
    {
        $this->adresse3 = $adresse3;
        return $this;
    }

    public function getVille3(): ?string
    {
        return $this->ville3;
    }

    public function setVille3(?string $ville3): self
    {
        $this->ville3 = $ville3;
        return $this;
    }

    public function getCodePostal3(): ?string
    {
        return $this->codePostal3;
    }

    public function setCodePostal3(?string $codePostal3): self
    {
        $this->codePostal3 = $codePostal3;
        return $this;
    }

    /**
     * @return Collection<int, PanierItem>
     */
    public function getPanierItems(): Collection
    {
        return $this->panierItems;
    }

    public function addPanierItem(PanierItem $panierItem): self
    {
        if (!$this->panierItems->contains($panierItem)) {
            $this->panierItems->add($panierItem);
            $panierItem->setClient($this);
        }
        return $this;
    }

    public function removePanierItem(PanierItem $panierItem): self
    {
        if ($this->panierItems->removeElement($panierItem)) {
            if ($panierItem->getClient() === $this) {
                $panierItem->setClient(null);
            }
        }
        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $resetTokenExpiresAt): self
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;
        return $this;
    }

    public function isResetTokenValid(): bool
    {
        if (!$this->resetToken || !$this->resetTokenExpiresAt) {
            return false;
        }

        return $this->resetTokenExpiresAt > new \DateTime();
    }

    public function getTypeClient(): string
    {
        return $this->typeClient;
    }

    public function setTypeClient(string $typeClient): self
    {
        $allowed = ['particulier', 'entreprise'];
        $this->typeClient = in_array($typeClient, $allowed, true) ? $typeClient : 'particulier';
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $allowed = ['user', 'admin'];
        $this->role = in_array($role, $allowed, true) ? $role : 'user';
        return $this;
    }
}
