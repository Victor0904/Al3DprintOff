<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'demande_personnalisation')]
class DemandePersonnalisation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $clientEmail = '';

    #[ORM\Column(length: 255)]
    private string $clientNom = 'Client';

    #[ORM\Column(length: 255)]
    private string $productName = '';

    #[ORM\Column(nullable: true)]
    private ?int $productId = null;

    #[ORM\Column(length: 50)]
    private string $statut = 'En attente';

    #[ORM\Column(type: 'json')]
    private array $fichiers = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateCreation;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getClientEmail(): string { return $this->clientEmail; }
    public function setClientEmail(string $v): static { $this->clientEmail = $v; return $this; }

    public function getClientNom(): string { return $this->clientNom; }
    public function setClientNom(string $v): static { $this->clientNom = $v; return $this; }

    public function getProductName(): string { return $this->productName; }
    public function setProductName(string $v): static { $this->productName = $v; return $this; }

    public function getProductId(): ?int { return $this->productId; }
    public function setProductId(?int $v): static { $this->productId = $v; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $v): static { $this->statut = $v; return $this; }

    public function getFichiers(): array { return $this->fichiers; }
    public function setFichiers(array $v): static { $this->fichiers = $v; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $v): static { $this->notes = $v; return $this; }

    public function getDateCreation(): \DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $v): static { $this->dateCreation = $v; return $this; }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'client_email'  => $this->clientEmail,
            'client_nom'    => $this->clientNom,
            'product_name'  => $this->productName,
            'product_id'    => $this->productId,
            'statut'        => $this->statut,
            'fichiers'      => $this->fichiers,
            'notes'         => $this->notes,
            'date_creation' => $this->dateCreation->format('Y-m-d H:i:s'),
        ];
    }
}
