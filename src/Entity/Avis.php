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
    #[ORM\Column(name: 'id_avis', nullable: true)]
private ?int $id = null;

    #[ORM\Column(name: 'id_therapeute', nullable: true)]
private ?int $idTherapeute = null;

    #[ORM\Column(name: 'id_utilisateur', nullable: true)]
private ?int $idUtilisateur = null;

    #[ORM\Column(name: 'note', nullable: true)]
private ?int $note = null;

    #[ORM\Column(name: 'commentaire', type: 'text', nullable: true)]
private ?string $commentaire = null;

    #[ORM\Column(name: 'date_avis', type: 'datetime', nullable: true)]
private ?\DateTimeInterface $dateAvis = null;

    public function getId(): ?int { return $this->id; }
    public function getIdTherapeute(): ?int { return $this->idTherapeute; }
    public function setIdTherapeute(int $idTherapeute): static { $this->idTherapeute = $idTherapeute; return $this; }
    public function getIdUtilisateur(): ?int { return $this->idUtilisateur; }
    public function setIdUtilisateur(int $idUtilisateur): static { $this->idUtilisateur = $idUtilisateur; return $this; }
    public function getNote(): ?int { return $this->note; }
    public function setNote(?int $note): static { $this->note = $note; return $this; }
    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): static { $this->commentaire = $commentaire; return $this; }
    public function getDateAvis(): ?\DateTimeInterface { return $this->dateAvis; }
    private function setDateAvis(?\DateTimeInterface $dateAvis): static { $this->dateAvis = $dateAvis; return $this; }
}