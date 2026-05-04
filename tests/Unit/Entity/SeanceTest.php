<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Seance;
use PHPUnit\Framework\TestCase;

class SeanceTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $s = new Seance();
        $this->assertNull($s->getId());
        $this->assertNull($s->getTitre());
        $this->assertNull($s->getDescription());
        $this->assertNull($s->getLieu());
        $this->assertNull($s->getDateDebut());
        $this->assertNull($s->getDateFin());
        $this->assertNull($s->getCapacite());
        $this->assertNull($s->getImage());
    }

    public function testSetTitre(): void
    {
        $s      = new Seance();
        $result = $s->setTitre('Yoga du matin');

        $this->assertSame('Yoga du matin', $s->getTitre());
        $this->assertSame($s, $result);
    }

    public function testSetLieu(): void
    {
        $s = new Seance();
        $s->setLieu('Tunis Centre');
        $this->assertSame('Tunis Centre', $s->getLieu());
    }

    public function testSetCapacitePositive(): void
    {
        $s      = new Seance();
        $result = $s->setCapacite(30);

        $this->assertSame(30, $s->getCapacite());
        $this->assertSame($s, $result);
    }

    public function testSetDates(): void
    {
        $s     = new Seance();
        $debut = new \DateTime('2030-01-01 10:00:00');
        $fin   = new \DateTime('2030-01-01 12:00:00');

        $s->setDateDebut($debut);
        $s->setDateFin($fin);

        $this->assertSame($debut, $s->getDateDebut());
        $this->assertSame($fin, $s->getDateFin());
    }

    public function testDateFinAfterDateDebut(): void
    {
        $debut = new \DateTime('2030-06-01 09:00:00');
        $fin   = new \DateTime('2030-06-01 11:00:00');

        $this->assertGreaterThan($debut, $fin);
    }

    public function testSetImage(): void
    {
        $s = new Seance();
        $s->setImage('69d6a9fd9b4d5.jpg');
        $this->assertSame('69d6a9fd9b4d5.jpg', $s->getImage());
    }

    public function testFullBuild(): void
    {
        $debut = new \DateTime('2030-03-15 08:00:00');
        $fin   = new \DateTime('2030-03-15 10:00:00');

        $s = (new Seance())
            ->setTitre('Méditation guidée')
            ->setDescription('Séance de méditation de pleine conscience')
            ->setLieu('Avenue Habib Bourguiba, Tunis')
            ->setCapacite(20)
            ->setDateDebut($debut)
            ->setDateFin($fin);

        $this->assertSame('Méditation guidée', $s->getTitre());
        $this->assertSame(20, $s->getCapacite());
        $this->assertSame($debut, $s->getDateDebut());
        $this->assertSame($fin, $s->getDateFin());
    }
}
