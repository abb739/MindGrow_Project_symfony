<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Seance;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SeanceController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('seance/index.html.twig');
    }

    #[Route('/seances', name: 'app_seances')]
    public function seances(EntityManagerInterface $em): Response
    {
        $seances = $em->getRepository(Seance::class)->findAll();

        // Grouper les séances par titre pour créer des cards
        $seancesGrouped = [];
        foreach ($seances as $seance) {
            $titre = $seance->getTitre();
            if (!isset($seancesGrouped[$titre])) {
                $seancesGrouped[$titre] = [];
            }
            $seancesGrouped[$titre][] = $seance;
        }

        return $this->render('seance/seances.html.twig', [
            'seancesGrouped' => $seancesGrouped,
            'reservations' => [],
        ]);
    }

    #[Route('/seance/{titre}', name: 'app_seance_detail')]
    public function detail(string $titre, EntityManagerInterface $em): Response
    {
        $seances = $em->getRepository(Seance::class)->findBy(['titre' => $titre]);

        if (empty($seances)) {
            throw $this->createNotFoundException('Aucune séance trouvée pour ce titre');
        }

        return $this->render('seance/detail.html.twig', [
            'titre' => $titre,
            'seances' => $seances,
        ]);
    }

    #[Route('/seance/{id}/reserve', name: 'app_seance_reserve', methods: ['GET', 'POST'])]
    public function reserve(
        Request $request,
        Seance $seance,
        EntityManagerInterface $em,
        UtilisateurRepository $utilisateurRepository
    ): Response {
        if ($request->isMethod('POST')) {
            $reservation = new Reservation();
            $reservation->setSeance($seance);
            $reservation->setDateReservation(new \DateTime());
            $reservation->setStatut('en attente');

            $user = $this->getUser();
            if ($user instanceof Utilisateur) {
                $reservation->setUtilisateur($user);
            } else {
                // fallback : premier utilisateur existant
                $utilisateur = $utilisateurRepository->findOneBy([]);
                if (!$utilisateur) {
                    throw new \RuntimeException('Aucun utilisateur pour enregistrer la réservation.');
                }
                $reservation->setUtilisateur($utilisateur);
            }

            $em->persist($reservation);
            $em->flush();

            $this->addFlash('success', 'Votre réservation a été enregistrée avec succès.');

            return $this->redirectToRoute('app_seance_detail', ['titre' => $seance->getTitre()]);
        }

        return $this->render('seance/reserve.html.twig', ['seance' => $seance]);
    }
}
