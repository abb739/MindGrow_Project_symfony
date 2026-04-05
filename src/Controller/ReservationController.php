<?php
namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Repository\SeanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ReservationController extends AbstractController
{
    #[Route('/reservations/new/{idSeance}', name: 'reservation_new')]
    public function new(int $idSeance, SeanceRepository $seanceRepo, ReservationRepository $reservationRepo, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');
        $seance = $seanceRepo->find($idSeance);
        if (!$seance) return $this->redirectToRoute('seances');

        $userId = $session->get('user_id');

        // Vérifier si déjà réservé
        $existing = $reservationRepo->findOneBy(['idSeance' => $idSeance, 'idUtilisateur' => $userId]);
        if ($existing && $existing->getStatut() !== 'annulée') {
            return $this->redirectToRoute('seances');
        }

        // Vérifier capacité
        $reservations = $reservationRepo->findBy(['idSeance' => $idSeance]);
        $actives = array_filter($reservations, fn($r) => $r->getStatut() !== 'annulée');
        if (count($actives) >= $seance->getCapacite()) {
            return $this->redirectToRoute('seances');
        }

        $reservation = new Reservation();
        $reservation->setIdSeance($idSeance);
        $reservation->setIdUtilisateur($userId);
        $reservation->setStatut('en attente');
        $reservation->setDateReservation(new \DateTime());
        $em->persist($reservation);
        $em->flush();

        return $this->redirectToRoute('mes_reservations');
    }

    #[Route('/reservations/annuler/{id}', name: 'reservation_annuler')]
    public function annuler(Reservation $reservation, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');
        $reservation->setStatut('annulée');
        $em->flush();
        return $this->redirectToRoute('mes_reservations');
    }

    #[Route('/mes-reservations', name: 'mes_reservations')]
    public function mesReservations(ReservationRepository $repo, SeanceRepository $seanceRepo, SessionInterface $session): Response
    {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');
        $userId = $session->get('user_id');
        $reservations = $repo->findBy(['idUtilisateur' => $userId]);
        $seances = [];
        foreach ($reservations as $r) {
            $seances[$r->getId()] = $seanceRepo->find($r->getIdSeance());
        }
        return $this->render('reservation/mes_reservations.html.twig', [
            'reservations' => $reservations,
            'seances' => $seances,
        ]);
    }

    // BACKOFFICE ADMIN
    #[Route('/admin/reservations', name: 'admin_reservations')]
    public function adminIndex(ReservationRepository $repo, SeanceRepository $seanceRepo, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        $reservations = $repo->findAll();
        $seances = [];
        foreach ($reservations as $r) {
            $seances[$r->getId()] = $seanceRepo->find($r->getIdSeance());
        }
        return $this->render('reservation/admin.html.twig', [
            'reservations' => $reservations,
            'seances' => $seances,
        ]);
    }

    #[Route('/admin/reservations/confirmer/{id}', name: 'admin_reservation_confirmer')]
    public function confirmer(Reservation $reservation, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        $reservation->setStatut('confirmée');
        $em->flush();
        return $this->redirectToRoute('admin_reservations');
    }

    #[Route('/admin/reservations/delete/{id}', name: 'admin_reservation_delete')]
    public function delete(Reservation $reservation, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        $em->remove($reservation);
        $em->flush();
        return $this->redirectToRoute('admin_reservations');
    }
}