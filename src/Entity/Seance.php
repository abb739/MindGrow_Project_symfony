<?php

namespace App\Entity;

use App\Repository\SeanceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SeanceRepository::class)]
#[ORM\Table(name: 'seance')]
class Seance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_seance', nullable: true)]
private ?int $id = null;

    #[ORM\Column(name: 'titre', length: 100)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: "Le titre doit contenir au moins 3 caractères",
        maxMessage: "Le titre ne doit pas dépasser 100 caractères"
    )]
    private ?string $titre = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(
        min: 5,
        minMessage: "La description doit contenir au moins 5 caractères"
    )]
    private ?string $description = null;

    #[ORM\Column(name: 'lieu', length: 150, nullable: true)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire")]
    private ?string $lieu = null;

    #[ORM\Column(name: 'date_debut', type: 'datetime', nullable: true)]
    #[Assert\NotNull(message: "La date de début est obligatoire")]
    #[Assert\GreaterThan("today", message: "La date de début doit être dans le futur")]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: 'date_fin', type: 'datetime', nullable: true)]
    #[Assert\NotNull(message: "La date de fin est obligatoire")]
    #[Assert\Expression(
        "this.getDateFin() > this.getDateDebut()",
        message: "La date de fin doit être après la date de début"
    )]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(name: 'capacite', nullable: true)]
    #[Assert\NotNull(message: "La capacité est obligatoire")]
    #[Assert\Positive(message: "La capacité doit être supérieure à 0")]
    private ?int $capacite = null;

    #[ORM\Column(name: 'image', length: 255, nullable: true)]
    #[Assert\File(
        maxSize: "2M",
        mimeTypes: ["image/jpeg", "image/png"],
        mimeTypesMessage: "L'image doit être en JPG ou PNG"
    )]
    private ?string $image = null;

    // ======================
    // GETTERS & SETTERS
    // ======================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    private function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    private function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(int $capacite): static
    {
        $this->capacite = $capacite;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }
}