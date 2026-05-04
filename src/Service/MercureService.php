<?php

namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercureService
{
    public function __construct(private HubInterface $hub) {}

    public function sendReservationUpdate(array $data): void
    {
        $update = new Update(
            'reservation',
            json_encode($data)
        );

        $this->hub->publish($update);
    }
}