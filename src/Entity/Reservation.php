<?php
namespace App\Entity;
use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_reservation', nullable: true)]
private ?int $id = null;

    #[ORM\Column(name: 'id_seance', nullable: true)]
private ?int $idSeance = null;

    #[ORM\Column(name: 'id_utilisateur', nullable: true)]
private ?int $idUtilisateur = null;

    #[ORM\Column(name: 'date_reservation', type: 'datetime', nullable: true)]
private ?\DateTimeInterface $dateReservation = null;

    #[ORM\Column(name: 'statut', length: 20, nullable: true)]
private ?string $statut = null;

    public function getId(): ?int { return $this->id; }
    public function getIdSeance(): ?int { return $this->idSeance; }
    public function setIdSeance(int $idSeance): static { $this->idSeance = $idSeance; return $this; }
    public function getIdUtilisateur(): ?int { return $this->idUtilisateur; }
    public function setIdUtilisateur(int $idUtilisateur): static { $this->idUtilisateur = $idUtilisateur; return $this; }
    public function getDateReservation(): ?\DateTimeInterface { return $this->dateReservation; }
    private function setDateReservation(?\DateTimeInterface $dateReservation): static { $this->dateReservation = $dateReservation; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
}