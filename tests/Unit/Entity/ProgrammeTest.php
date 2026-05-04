<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Programme;
use PHPUnit\Framework\TestCase;

class ProgrammeTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $p = new Programme();
        $this->assertNull($p->getId());
        $this->assertNull($p->getTitre());
        $this->assertNull($p->getDescription());
        $this->assertNull($p->getImage());
        $this->assertNull($p->getVideo());
        $this->assertNull($p->getIdCategorie());
    }

    public function testSetTitreReturnsFluent(): void
    {
        $p      = new Programme();
        $result = $p->setTitre('Yoga matinal');

        $this->assertSame('Yoga matinal', $p->getTitre());
        $this->assertSame($p, $result);
    }

    public function testSetDescriptionNullable(): void
    {
        $p = new Programme();
        $p->setDescription('Une description.');
        $this->assertSame('Une description.', $p->getDescription());

        $p->setDescription(null);
        $this->assertNull($p->getDescription());
    }

    public function testSetImage(): void
    {
        $p = new Programme();
        $p->setImage('uploads/programmes/images/prog_abc.jpg');
        $this->assertSame('uploads/programmes/images/prog_abc.jpg', $p->getImage());
    }

    public function testSetVideo(): void
    {
        $p = new Programme();
        $p->setVideo('uploads/programmes/videos/prog_abc.mp4');
        $this->assertSame('uploads/programmes/videos/prog_abc.mp4', $p->getVideo());
    }

    public function testSetIdCategorie(): void
    {
        $p      = new Programme();
        $result = $p->setIdCategorie(5);

        $this->assertSame(5, $p->getIdCategorie());
        $this->assertSame($p, $result);
    }

    public function testFullBuild(): void
    {
        $p = (new Programme())
            ->setTitre('Méditation')
            ->setDescription('Exercice de respiration')
            ->setIdCategorie(7)
            ->setImage('img.jpg')
            ->setVideo('vid.mp4');

        $this->assertSame('Méditation', $p->getTitre());
        $this->assertSame('Exercice de respiration', $p->getDescription());
        $this->assertSame(7, $p->getIdCategorie());
        $this->assertSame('img.jpg', $p->getImage());
        $this->assertSame('vid.mp4', $p->getVideo());
    }
}
