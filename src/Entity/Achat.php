<?php
namespace App\Entity;
use App\Repository\AchatRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AchatRepository::class)]
#[ORM\Table(name: 'achat')]
class Achat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_achat', nullable: true)]
private ?int $id = null;

    #[ORM\Column(name: 'id_abonnement', nullable: true)]
private ?int $idAbonnement = null;

    #[ORM\Column(name: 'id_utilisateur', nullable: true)]
private ?int $idUtilisateur = null;

    #[ORM\Column(name: 'date_achat', type: 'datetime', nullable: true)]
private ?\DateTimeInterface $dateAchat = null;

    #[ORM\Column(name: 'statut', length: 20, nullable: true)]
private ?string $statut = null;

    public function getId(): ?int { return $this->id; }
    public function getIdAbonnement(): ?int { return $this->idAbonnement; }
    public function setIdAbonnement(int $idAbonnement): static { $this->idAbonnement = $idAbonnement; return $this; }
    public function getIdUtilisateur(): ?int { return $this->idUtilisateur; }
    public function setIdUtilisateur(int $idUtilisateur): static { $this->idUtilisateur = $idUtilisateur; return $this; }
    public function getDateAchat(): ?\DateTimeInterface { return $this->dateAchat; }
    private function setDateAchat(?\DateTimeInterface $dateAchat): static { $this->dateAchat = $dateAchat; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
}