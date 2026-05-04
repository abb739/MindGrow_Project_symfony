<?php

namespace App\Tests\Entity;

use App\Entity\Utilisateur;
use PHPUnit\Framework\TestCase;

class UtilisateurTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $utilisateur = new Utilisateur();

        $utilisateur->setNom('Doe');
        $this->assertEquals('Doe', $utilisateur->getNom());

        $utilisateur->setPrenom('John');
        $this->assertEquals('John', $utilisateur->getPrenom());

        $utilisateur->setEmail('john.doe@example.com');
        $this->assertEquals('john.doe@example.com', $utilisateur->getEmail());

        $utilisateur->setMotDePasse('securepassword123');
        $this->assertEquals('securepassword123', $utilisateur->getMotDePasse());

        $utilisateur->setRole('ROLE_USER');
        $this->assertEquals('ROLE_USER', $utilisateur->getRole());


        $utilisateur->setIsVerified(true);
        $this->assertTrue($utilisateur->isVerified());

        $utilisateur->setThemePreference('dark');
        $this->assertEquals('dark', $utilisateur->getThemePreference());
    }

    public function testDefaultValues(): void
    {
        $utilisateur = new Utilisateur();

        $this->assertFalse($utilisateur->isVerified());
        $this->assertEquals('auto', $utilisateur->getThemePreference());
        $this->assertNull($utilisateur->getId());
    }
}
