<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'achat')]
class Achat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_achat', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Abonnement::class, inversedBy: 'achats')]
    #[ORM\JoinColumn(name: 'id_abonnement', referencedColumnName: 'id_abonnement', nullable: false, onDelete: 'CASCADE')]
    private ?Abonnement $abonnement = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'achats')]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id_utilisateur', nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(name: 'date_achat', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateAchat = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true, columnDefinition: "ENUM('actif','expiré','annulé')")]
    private ?string $statut = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAbonnement(): ?Abonnement
    {
        return $this->abonnement;
    }

    public function setAbonnement(?Abonnement $abonnement): self
    {
        $this->abonnement = $abonnement;
        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getDateAchat(): ?\DateTimeInterface
    {
        return $this->dateAchat;
    }

    public function setDateAchat(?\DateTimeInterface $dateAchat): self
    {
        $this->dateAchat = $dateAchat;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }
}
