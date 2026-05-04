<?php

namespace App\Tests\Entity;

use App\Entity\Programme;
use PHPUnit\Framework\TestCase;

class ProgrammeTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $programme = new Programme();

        $programme->setTitre('Programme de relaxation');
        $this->assertEquals('Programme de relaxation', $programme->getTitre());

        $programme->setDescription('Une série de vidéos pour se détendre.');
        $this->assertEquals('Une série de vidéos pour se détendre.', $programme->getDescription());

        $programme->setIdCategorie(1);
        $this->assertEquals(1, $programme->getIdCategorie());

        $programme->setImage('image.jpg');
        $this->assertEquals('image.jpg', $programme->getImage());

        $programme->setVideo('video.mp4');
        $this->assertEquals('video.mp4', $programme->getVideo());

        $this->assertNull($programme->getId());
    }
}
