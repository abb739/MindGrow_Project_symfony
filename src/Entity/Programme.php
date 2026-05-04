<?php
namespace App\Entity;
use App\Repository\ProgrammeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgrammeRepository::class)]
#[ORM\Table(name: 'programme')]
class Programme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_programme', nullable: true)]
private ?int $id = null;

    #[ORM\Column(name: 'id_categorie', nullable: true)]
private ?int $idCategorie = null;

    #[ORM\Column(name: 'titre', length: 100, nullable: true)]
private ?string $titre = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
private ?string $description = null;

    #[ORM\Column(name: 'image', length: 255, nullable: true)]
private ?string $image = null;

    #[ORM\Column(name: 'video', length: 255, nullable: true)]
private ?string $video = null;

    public function getId(): ?int { return $this->id; }
    public function getIdCategorie(): ?int { return $this->idCategorie; }
    public function setIdCategorie(int $idCategorie): static { $this->idCategorie = $idCategorie; return $this; }
    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }
    public function getVideo(): ?string { return $this->video; }
    public function setVideo(?string $video): static { $this->video = $video; return $this; }
}