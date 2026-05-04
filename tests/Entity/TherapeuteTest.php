<?php

namespace App\Tests\Entity;

use App\Entity\Therapeute;
use PHPUnit\Framework\TestCase;

class TherapeuteTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $therapeute = new Therapeute();

        $therapeute->setNom('Smith');
        $this->assertEquals('Smith', $therapeute->getNom());

        $therapeute->setPrenom('Jane');
        $this->assertEquals('Jane', $therapeute->getPrenom());

        $therapeute->setEmail('jane.smith@example.com');
        $this->assertEquals('jane.smith@example.com', $therapeute->getEmail());

        $therapeute->setSpecialite('Psychologue');
        $this->assertEquals('Psychologue', $therapeute->getSpecialite());

        $therapeute->setTelephone('123456789');
        $this->assertEquals('123456789', $therapeute->getTelephone());

        $therapeute->setStatutCertificat('validé');

        $this->assertNull($therapeute->getId());
    }
}
