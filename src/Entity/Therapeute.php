<?php
namespace App\Entity;
use App\Repository\TherapeuteRepository;
use Doctrine\ORM\Mapping as ORM;
use App\ValueObject\Email;

#[ORM\Entity(repositoryClass: TherapeuteRepository::class)]
#[ORM\Table(name: 'therapeute')]
class Therapeute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_therapeute', nullable: true)]
private ?int $id = null;

    #[ORM\Column(name: 'nom', length: 100, nullable: true)]
private ?string $nom = null;

    #[ORM\Column(name: 'prenom', length: 100, nullable: true)]
private ?string $prenom = null;

    #[ORM\Column(name: 'image', length: 255, nullable: true)]
private ?string $image = null;

    #[ORM\Column(name: 'certificat', length: 255, nullable: true)]
private ?string $certificat = null;

    #[ORM\Column(name: 'statut_certificat', length: 20, nullable: true)]
private ?string $statutCertificat = null;

    #[ORM\Column(name: 'certificat_texte', type: 'text', nullable: true)]
private ?string $certificatTexte = null;

    #[ORM\Column(name: 'specialite', length: 100, nullable: true)]
private ?string $specialite = null;

    #[ORM\Embedded(class: Email::class, columnPrefix: false)]
private ?Email $email = null;

    #[ORM\Column(name: 'telephone', length: 20, nullable: true)]
private ?string $telephone = null;

    #[ORM\Column(name: 'date_inscription', type: 'datetime', nullable: true)]
private ?\DateTimeInterface $dateInscription = null;

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
    public function getEmail(): ?string { return $this->email?->getValue(); }
    public function setEmail(?string $email): static { $this->email = $email ? new Email($email) : null; return $this; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }
    public function getDateInscription(): ?\DateTimeInterface { return $this->dateInscription; }
    private function setDateInscription(?\DateTimeInterface $dateInscription): static { $this->dateInscription = $dateInscription; return $this; }
    public function getStatutCertificat(): ?string { return $this->statutCertificat; }
    public function setStatutCertificat(?string $s): static { $this->statutCertificat = $s; return $this; }
    public function getCertificatTexte(): ?string { return $this->certificatTexte; }
    public function setCertificatTexte(?string $t): static { $this->certificatTexte = $t; return $this; }
}