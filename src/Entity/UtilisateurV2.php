<?php
namespace App\Entity;
use App\Repository\UtilisateurV2Repository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UtilisateurV2Repository::class)]
#[ORM\Table(name: 'utilisateur_v2')]
class UtilisateurV2
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_utilisateur')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom', length: 100)]
    private ?string $nom = null;

    #[ORM\Column(name: 'prenom', length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(name: 'email', length: 150, unique: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'mot_de_passe', length: 255)]
    private ?string $motDePasse = null;

    #[ORM\Column(name: 'role', length: 20)]
    private ?string $role = null;

    #[ORM\Column(name: 'date_inscription', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateInscription = null;

    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = trim($nom); return $this; }
    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = trim($prenom); return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = trim($email); return $this; }
    public function getMotDePasse(): ?string { return $this->motDePasse; }
    public function setMotDePasse(string $motDePasse): static { $this->motDePasse = $motDePasse; return $this; }
    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }
    public function getDateInscription(): ?\DateTimeInterface { return $this->dateInscription; }
    public function setDateInscription(?\DateTimeInterface $dateInscription): static { $this->dateInscription = $dateInscription; return $this; }

    /**
     * ✅ CONTRÔLE DE SAISIE - Validation methods
     */
    public function validateNom(): bool
    {
        return !empty($this->nom) && strlen($this->nom) >= 2 && strlen($this->nom) <= 100;
    }

    public function validatePrenom(): bool
    {
        return !empty($this->prenom) && strlen($this->prenom) >= 2 && strlen($this->prenom) <= 100;
    }

    public function validateEmail(): bool
    {
        return !empty($this->email) && filter_var($this->email, FILTER_VALIDATE_EMAIL) && strlen($this->email) <= 150;
    }

    public function validateMotDePasse(): bool
    {
        return !empty($this->motDePasse) && strlen($this->motDePasse) >= 6 && strlen($this->motDePasse) <= 255;
    }

    public function validateRole(): bool
    {
        return in_array($this->role, ['admin', 'client']);
    }

    public function validateAll(): array
    {
        $errors = [];
        if (!$this->validateNom()) $errors[] = 'Nom: 2-100 caractères requis';
        if (!$this->validatePrenom()) $errors[] = 'Prénom: 2-100 caractères requis';
        if (!$this->validateEmail()) $errors[] = 'Email: format invalide ou vide';
        if (!$this->validateMotDePasse()) $errors[] = 'Mot de passe: minimum 6 caractères';
        if (!$this->validateRole()) $errors[] = 'Rôle: admin ou client uniquement';
        return $errors;
    }
}
