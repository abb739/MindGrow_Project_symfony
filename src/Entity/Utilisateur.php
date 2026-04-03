<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'utilisateur')]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_utilisateur', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $nom;

    #[ORM\Column(length: 100)]
    private string $prenom;

    #[ORM\Column(length: 150, unique: true)]
    private string $email;

    #[ORM\Column(name: 'mot_de_passe', length: 255)]
    private string $motDePasse;

    #[ORM\Column(name: 'date_inscription', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(type: 'string', length: 20, columnDefinition: "ENUM('admin','client')")]
    private string $role;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Achat::class, orphanRemoval: true)]
    private Collection $achats;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Avis::class, orphanRemoval: true)]
    private Collection $avis;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Favoris::class, orphanRemoval: true)]
    private Collection $favoris;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Reservation::class, orphanRemoval: true)]
    private Collection $reservations;

    public function __construct()
    {
        $this->achats = new ArrayCollection();
        $this->avis = new ArrayCollection();
        $this->favoris = new ArrayCollection();
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getMotDePasse(): string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): self
    {
        $this->motDePasse = $motDePasse;
        return $this;
    }

    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    public function setDateInscription(?\DateTimeInterface $dateInscription): self
    {
        $this->dateInscription = $dateInscription;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    /** @return Collection|Achat[] */
    public function getAchats(): Collection
    {
        return $this->achats;
    }

    public function addAchat(Achat $achat): self
    {
        if (!$this->achats->contains($achat)) {
            $this->achats->add($achat);
            $achat->setUtilisateur($this);
        }

        return $this;
    }

    public function removeAchat(Achat $achat): self
    {
        if ($this->achats->removeElement($achat)) {
            if ($achat->getUtilisateur() === $this) {
                $achat->setUtilisateur(null);
            }
        }

        return $this;
    }

    /** @return Collection|Avis[] */
    public function getAvis(): Collection
    {
        return $this->avis;
    }

    public function addAvis(Avis $avis): self
    {
        if (!$this->avis->contains($avis)) {
            $this->avis->add($avis);
            $avis->setUtilisateur($this);
        }

        return $this;
    }

    public function removeAvis(Avis $avis): self
    {
        if ($this->avis->removeElement($avis)) {
            if ($avis->getUtilisateur() === $this) {
                $avis->setUtilisateur(null);
            }
        }

        return $this;
    }

    /** @return Collection|Favoris[] */
    public function getFavoris(): Collection
    {
        return $this->favoris;
    }

    public function addFavoris(Favoris $favori): self
    {
        if (!$this->favoris->contains($favori)) {
            $this->favoris->add($favori);
            $favori->setUtilisateur($this);
        }

        return $this;
    }

    public function removeFavoris(Favoris $favori): self
    {
        if ($this->favoris->removeElement($favori)) {
            if ($favori->getUtilisateur() === $this) {
                $favori->setUtilisateur(null);
            }
        }

        return $this;
    }

    /** @return Collection|Reservation[] */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setUtilisateur($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getUtilisateur() === $this) {
                $reservation->setUtilisateur(null);
            }
        }

        return $this;
    }
}

