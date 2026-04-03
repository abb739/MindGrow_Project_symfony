<?php

namespace App\Entity;

use App\Repository\TherapeuteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TherapeuteRepository::class)]
#[ORM\Table(name: 'therapeute')]
class Therapeute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_therapeute')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $certificat = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $specialite = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(name: 'date_inscription', nullable: true)]
    private ?\DateTime $dateInscription = null;

    #[ORM\OneToMany(mappedBy: 'therapeute', targetEntity: Avis::class, orphanRemoval: true)]
    private Collection $avis;

    public function __construct()
    {
        $this->avis = new ArrayCollection();
        $this->dateInscription = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function getCertificat(): ?string { return $this->certificat; }
    public function setCertificat(?string $certificat): static { $this->certificat = $certificat; return $this; }

    public function getSpecialite(): ?string { return $this->specialite; }
    public function setSpecialite(?string $specialite): static { $this->specialite = $specialite; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }

    public function getDateInscription(): ?\DateTime { return $this->dateInscription; }
    public function setDateInscription(?\DateTime $dateInscription): static { $this->dateInscription = $dateInscription; return $this; }

    public function getAvis(): Collection { return $this->avis; }

    public function getMoyenneNote(): float
    {
        if ($this->avis->isEmpty()) return 0.0;
        $total = array_sum($this->avis->map(fn($a) => $a->getNote())->toArray());
        return round($total / $this->avis->count(), 1);
    }

    public function __toString(): string { return $this->nom . ' ' . $this->prenom; }
}