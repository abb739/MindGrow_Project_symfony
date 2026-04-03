<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'avis')]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_avis', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Therapeute::class, inversedBy: 'avis')]
    #[ORM\JoinColumn(name: 'id_therapeute', referencedColumnName: 'id_therapeute', nullable: false, onDelete: 'CASCADE')]
    private ?Therapeute $therapeute = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'avis')]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id_utilisateur', nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $note = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(name: 'date_avis', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateAvis = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTherapeute(): ?Therapeute
    {
        return $this->therapeute;
    }

    public function setTherapeute(?Therapeute $therapeute): self
    {
        $this->therapeute = $therapeute;
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

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(?int $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getDateAvis(): ?\DateTimeInterface
    {
        return $this->dateAvis;
    }

    public function setDateAvis(?\DateTimeInterface $dateAvis): self
    {
        $this->dateAvis = $dateAvis;
        return $this;
    }
}
