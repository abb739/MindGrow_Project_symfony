<?php

namespace App\Tests\Entity;

use App\Entity\Abonnement;
use PHPUnit\Framework\TestCase;

class AbonnementTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $abonnement = new Abonnement();

        $abonnement->setNom('Premium');
        $this->assertEquals('Premium', $abonnement->getNom());

        $abonnement->setDescription('Accès complet à tous les programmes');
        $this->assertEquals('Accès complet à tous les programmes', $abonnement->getDescription());

        $abonnement->setPrix('49.99');
        $this->assertEquals('49.99', $abonnement->getPrix());

        $abonnement->setDureeMois(12);
        $this->assertEquals(12, $abonnement->getDureeMois());

        $this->assertNull($abonnement->getId());
    }
}
