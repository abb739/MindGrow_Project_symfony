<?php
namespace App\Entity;
use App\Repository\AbonnementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AbonnementRepository::class)]
#[ORM\Table(name: 'abonnement')]
class Abonnement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_abonnement', nullable: true)]
private ?int $id = null;

    #[ORM\Column(name: 'nom', length: 100, nullable: true)]
private ?string $nom = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
private ?string $description = null;

    #[ORM\Column(name: 'prix', type: 'decimal', precision: 10, scale: 2, nullable: true)]
private ?string $prix = null;

    #[ORM\Column(name: 'duree_mois', nullable: true)]
private ?int $dureeMois = null;

    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getPrix(): ?string { return $this->prix; }
    public function setPrix(string $prix): static { $this->prix = $prix; return $this; }
    public function getDureeMois(): ?int { return $this->dureeMois; }
    public function setDureeMois(int $dureeMois): static { $this->dureeMois = $dureeMois; return $this; }
}