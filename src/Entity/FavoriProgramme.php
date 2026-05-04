<?php
namespace App\Entity;

use App\Repository\FavoriProgrammeRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Blameable\Traits\BlameableEntity;

#[ORM\Entity(repositoryClass: FavoriProgrammeRepository::class)]
#[ORM\Table(name: 'favori_programme')]
#[ORM\UniqueConstraint(name: 'unique_favori', columns: ['id_utilisateur', 'id_programme'])]
class FavoriProgramme
{
    use BlameableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_favori', nullable: true)]
private ?int $id = null;

    #[ORM\Column(name: 'id_utilisateur', nullable: true)]
private ?int $idUtilisateur = null;

    #[ORM\Column(name: 'id_programme', nullable: true)]
private ?int $idProgramme = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_at', type: 'datetime')]
private \DateTimeInterface $createdAt;

    public function __construct()
    {
    }

    public function getId(): ?int { return $this->id; }

    public function getIdUtilisateur(): ?int { return $this->idUtilisateur; }
    public function setIdUtilisateur(int $idUtilisateur): static { $this->idUtilisateur = $idUtilisateur; return $this; }

    public function getIdProgramme(): ?int { return $this->idProgramme; }
    public function setIdProgramme(int $idProgramme): static { $this->idProgramme = $idProgramme; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    private function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
