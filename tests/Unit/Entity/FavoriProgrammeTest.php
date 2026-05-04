<?php

namespace App\Tests\Unit\Entity;

use App\Entity\FavoriProgramme;
use PHPUnit\Framework\TestCase;

class FavoriProgrammeTest extends TestCase
{
    public function testConstructorSetsCreatedAt(): void
    {
        $before = new \DateTime();
        $favori = new FavoriProgramme();
        $after  = new \DateTime();

        $this->assertInstanceOf(\DateTimeInterface::class, $favori->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $favori->getCreatedAt());
        $this->assertLessThanOrEqual($after, $favori->getCreatedAt());
    }

    public function testSetAndGetIdUtilisateur(): void
    {
        $favori = new FavoriProgramme();
        $result = $favori->setIdUtilisateur(42);

        $this->assertSame(42, $favori->getIdUtilisateur());
        $this->assertSame($favori, $result); // fluent interface
    }

    public function testSetAndGetIdProgramme(): void
    {
        $favori = new FavoriProgramme();
        $result = $favori->setIdProgramme(7);

        $this->assertSame(7, $favori->getIdProgramme());
        $this->assertSame($favori, $result);
    }

    public function testSetCreatedAt(): void
    {
        $favori = new FavoriProgramme();
        $dt     = new \DateTime('2026-01-01 12:00:00');
        $result = $favori->setCreatedAt($dt);

        $this->assertSame($dt, $favori->getCreatedAt());
        $this->assertSame($favori, $result);
    }

    public function testIdIsNullBeforePersist(): void
    {
        $favori = new FavoriProgramme();
        $this->assertNull($favori->getId());
    }

    public function testUniqueConstraintFields(): void
    {
        $f1 = (new FavoriProgramme())->setIdUtilisateur(1)->setIdProgramme(10);
        $f2 = (new FavoriProgramme())->setIdUtilisateur(1)->setIdProgramme(10);

        // Same logical key — uniqueness enforced at DB level, entities are identical
        $this->assertSame($f1->getIdUtilisateur(), $f2->getIdUtilisateur());
        $this->assertSame($f1->getIdProgramme(), $f2->getIdProgramme());
    }
}
