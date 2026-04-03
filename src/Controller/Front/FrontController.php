<?php

namespace App\Controller\Front;

use App\Entity\Seance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{
    // Page pour tester le layout (index.html.twig ou base)
    #[Route('/test-base', name: 'front_test_base')]
    public function testBase(): Response
    {
        return $this->render('seance/index.html.twig');
    }

    // Page des séances
    #[Route('/seances', name: 'front_seances')]
    public function seances(EntityManagerInterface $em): Response
    {
        // Récupère toutes les séances depuis la base de données
        $seances = $em->getRepository(Seance::class)->findAll();

        // Groupe les séances par titre
        $seancesGrouped = [];
        foreach ($seances as $seance) {
            $titre = $seance->getTitre();
            if (!isset($seancesGrouped[$titre])) {
                $seancesGrouped[$titre] = [];
            }
            $seancesGrouped[$titre][] = $seance;
        }

        // Si tu veux intégrer les réservations pour l'utilisateur connecté :
        // $reservations = $this->getUser() ? $this->getUser()->getReservations() : [];

        // Rend le template seances.html.twig
        return $this->render('seance/seances.html.twig', [
            'seancesGrouped' => $seancesGrouped,
            // 'reservations' => $reservations, // Décommenter si tu as les réservations
        ]);
    }
}
