<?php
namespace App\Entity;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use App\ValueObject\Email;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_utilisateur', nullable: true)]
private ?int $id = null;

    #[ORM\Column(name: 'nom', length: 100, nullable: true)]
private ?string $nom = null;

    #[ORM\Column(name: 'prenom', length: 100, nullable: true)]
private ?string $prenom = null;

    #[ORM\Embedded(class: Email::class, columnPrefix: false)]
private ?Email $email = null;

    #[ORM\Column(name: 'mot_de_passe', length: 255, nullable: true)]
private ?string $motDePasse = null;

    #[ORM\Column(name: 'role', length: 20, nullable: true)]
private ?string $role = null;

    #[ORM\Column(name: 'reset_token', length: 255, nullable: true, options: ['sensitive' => true])]
private ?string $resetToken = null;

    #[ORM\Column(name: 'reset_token_expires_at', type: 'datetime', nullable: true, options: ['sensitive' => true])]
private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\Column(name: 'is_verified', type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(name: 'verification_token', length: 255, nullable: true, options: ['sensitive' => true])]
private ?string $verificationToken = null;

    #[ORM\Column(name: 'date_inscription', type: 'datetime', nullable: true)]
private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(name: 'theme_preference', length: 10, nullable: true, options: ['default' => 'auto'])]
    private ?string $themePreference = 'auto';

    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }
    public function getEmail(): ?string { return $this->email?->getValue(); }
    public function setEmail(?string $email): static { $this->email = $email ? new Email($email) : null; return $this; }
    public function getMotDePasse(): ?string { return $this->motDePasse; }
    public function setMotDePasse(string $motDePasse): static { $this->motDePasse = $motDePasse; return $this; }
    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }
    public function getDateInscription(): ?\DateTimeInterface { return $this->dateInscription; }
    private function setDateInscription(?\DateTimeInterface $dateInscription): static { $this->dateInscription = $dateInscription; return $this; }

    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $resetToken): static { $this->resetToken = $resetToken; return $this; }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface { return $this->resetTokenExpiresAt; }
    private function setResetTokenExpiresAt(?\DateTimeInterface $resetTokenExpiresAt): static { $this->resetTokenExpiresAt = $resetTokenExpiresAt; return $this; }

    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $isVerified): static { $this->isVerified = $isVerified; return $this; }

    public function getVerificationToken(): ?string { return $this->verificationToken; }
    public function setVerificationToken(?string $verificationToken): static { $this->verificationToken = $verificationToken; return $this; }

    public function getThemePreference(): ?string { return $this->themePreference ?? 'auto'; }
    public function setThemePreference(?string $theme): static { $this->themePreference = $theme; return $this; }
}