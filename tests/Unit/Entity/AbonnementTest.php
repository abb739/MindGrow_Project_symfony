<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Abonnement;
use PHPUnit\Framework\TestCase;

class AbonnementTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $a = new Abonnement();
        $this->assertNull($a->getId());
        $this->assertNull($a->getNom());
        $this->assertNull($a->getPrix());
        $this->assertNull($a->getDureeMois());
        $this->assertNull($a->getDescription());
    }

    public function testSetNom(): void
    {
        $a      = new Abonnement();
        $result = $a->setNom('Pack Premium');

        $this->assertSame('Pack Premium', $a->getNom());
        $this->assertSame($a, $result);
    }

    public function testSetPrix(): void
    {
        $a      = new Abonnement();
        $result = $a->setPrix('50.00');

        $this->assertSame('50.00', $a->getPrix());
        $this->assertSame($a, $result);
    }

    public function testSetDureeMois(): void
    {
        $a      = new Abonnement();
        $result = $a->setDureeMois(12);

        $this->assertSame(12, $a->getDureeMois());
        $this->assertSame($a, $result);
    }

    public function testSetDescriptionNullable(): void
    {
        $a = new Abonnement();
        $a->setDescription('Accès illimité');
        $this->assertSame('Accès illimité', $a->getDescription());

        $a->setDescription(null);
        $this->assertNull($a->getDescription());
    }

    /**
     * Simulates the monthly price calculation used in GeminiService::chatAbonnement()
     */
    public function testPrixMensuelCalculation(): void
    {
        $a = (new Abonnement())
            ->setNom('Pack Annuel')
            ->setPrix('600.00')
            ->setDureeMois(12);

        $prixMensuel = (float)$a->getPrix() / (int)$a->getDureeMois();
        $this->assertEqualsWithDelta(50.0, $prixMensuel, 0.001);
    }

    public function testDureeMoisVariants(): void
    {
        foreach ([1, 3, 6, 12, 24] as $duree) {
            $a = (new Abonnement())->setDureeMois($duree);
            $this->assertSame($duree, $a->getDureeMois());
        }
    }
}
