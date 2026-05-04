<?php

namespace App\Tests\Entity;

use App\Entity\Seance;
use PHPUnit\Framework\TestCase;

class SeanceTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $seance = new Seance();

        $seance->setTitre('Séance de méditation');
        $this->assertEquals('Séance de méditation', $seance->getTitre());

        $seance->setDescription('Méditation guidée de 30 minutes.');
        $this->assertEquals('Méditation guidée de 30 minutes.', $seance->getDescription());

        $seance->setLieu('Salle Zen');
        $this->assertEquals('Salle Zen', $seance->getLieu());


        $seance->setCapacite(20);
        $this->assertEquals(20, $seance->getCapacite());

        $this->assertNull($seance->getId());
    }
}
