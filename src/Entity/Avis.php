<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvisRepository::class)]
#[ORM\Table(name: 'avis')]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_avis')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'avis')]
    #[ORM\JoinColumn(name: 'id_therapeute', referencedColumnName: 'id_therapeute', nullable: false)]
    private ?Therapeute $therapeute = null;

    #[ORM\Column(name: 'id_utilisateur')]
    private ?int $idUtilisateur = null;

    #[ORM\Column]
    private ?int $note = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(name: 'date_avis', nullable: true)]
    private ?\DateTime $dateAvis = null;

    public function __construct()
    {
        $this->dateAvis = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getTherapeute(): ?Therapeute { return $this->therapeute; }
    public function setTherapeute(?Therapeute $therapeute): static { $this->therapeute = $therapeute; return $this; }

    public function getIdUtilisateur(): ?int { return $this->idUtilisateur; }
    public function setIdUtilisateur(int $idUtilisateur): static { $this->idUtilisateur = $idUtilisateur; return $this; }

    public function getNote(): ?int { return $this->note; }
    public function setNote(int $note): static { $this->note = $note; return $this; }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): static { $this->commentaire = $commentaire; return $this; }

    public function getDateAvis(): ?\DateTime { return $this->dateAvis; }
    public function setDateAvis(?\DateTime $dateAvis): static { $this->dateAvis = $dateAvis; return $this; }
}