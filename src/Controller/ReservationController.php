<?php
namespace App\Controller;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use App\Service\NotificationService;
use App\Entity\Reservation;
use App\Service\PdfService;
use App\Repository\ReservationRepository;
use App\Repository\SeanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\QrCodeService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Repository\UtilisateurRepository;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;


class ReservationController extends AbstractController
{
    private MailerInterface $mailer;

private HubInterface $hub;

public function __construct(
    MailerInterface $mailer,
    HubInterface $hub
) {
    $this->mailer = $mailer;
    $this->hub = $hub;
}
    #[Route('/reservations/new/{idSeance}', name: 'reservation_new')]
    public function new(
        int $idSeance,
        SeanceRepository $seanceRepo,
        ReservationRepository $reservationRepo,
        EntityManagerInterface $em,
        SessionInterface $session,
        NotificationService $notificationService,
UtilisateurRepository $userRepo,
    ): Response {


        if (!$session->get('user_id')) {
            return $this->redirectToRoute('login');
        }
        $seance = $seanceRepo->find($idSeance);
        if (!$seance) {
            return $this->redirectToRoute('seances');
        }
        
        $userId = $session->get('user_id');

        // déjà réservé
        $existing = $reservationRepo->findOneBy([
            'idSeance' => $idSeance,
            'idUtilisateur' => $userId
        ]);

        if ($existing && $existing->getStatut() !== 'annulée') {
            return $this->redirectToRoute('seances');
        }

        // capacité
        $reservations = $reservationRepo->findBy(['idSeance' => $idSeance]);
        $actives = array_filter($reservations, fn($r) => $r->getStatut() !== 'annulée');

        if (count($actives) >= $seance->getCapacite()) {
            return $this->redirectToRoute('seances');
        }

        // créer réservation
        $reservation = new Reservation();
$reservation->setIdSeance($idSeance);
$reservation->setIdUtilisateur($userId);
$reservation->setStatut('en attente');
$reservation->setDateReservation(new \DateTime());

$em->persist($reservation);

$em->flush();

$update = new Update(
    'admin-notifications',
    json_encode([
        'user' => $session->get('user_nom'),
        'seance' => $seance->getTitre(),
        'message' => 'Nouvelle réservation'
    ])
);

//$this->hub->publish($update);
    // log mais ne bloque pas
     // 📩 ENVOI EMAIL (CORRIGÉ)
$user = $userRepo->find($userId);
$userEmail = $user?->getEmail();

if ($userEmail) {
    $email = (new Email())
        ->from('adminmindrow@gmail.com')
        ->to($userEmail)
        ->subject('Réservation confirmée')
        ->html('
        <div style="max-width:600px;margin:auto;font-family:Arial;background:#f4f7fb;padding:20px;border-radius:12px;">

            <div style="text-align:center;background:#0b7a8f;color:white;padding:20px;border-radius:12px 12px 0 0;">
                <h1 style="margin:0;">🌿 MindGrow</h1>
                <p style="margin:5px 0;">Confirmation de réservation</p>
            </div>

            <div style="background:white;padding:20px;border-radius:0 0 12px 12px;">

                <h2 style="color:#0b7a8f;">Bonjour ' . $session->get('user_nom') . ' 👋</h2>

                <p>Votre réservation a été confirmée avec succès 🎉</p>

                <div style="background:#f0f9ff;padding:15px;border-radius:10px;margin-top:15px;">
                    <p><b>🧘 Séance :</b> ' . $seance->getTitre() . '</p>
                    <p><b>📅 Date :</b> ' . $seance->getDateDebut()->format('d/m/Y H:i') . '</p>
                    <p><b>📍 Lieu :</b> ' . $seance->getLieu() . '</p>
                </div>

                <p style="margin-top:20px;">
                    Merci pour votre confiance 💚<br>
                    L’équipe MindGrow
                </p>

                <div style="text-align:center;margin-top:20px;">
                    <a href="http://localhost:8000/mes-reservations"
                       style="background:#0b7a8f;color:white;padding:10px 20px;border-radius:8px;text-decoration:none;">
                       Voir mes réservations
                    </a>
                </div>

            </div>
        </div>
    ');

    $this->mailer->send($email);
}

        return $this->redirectToRoute('mes_reservations');
    }
#[Route('/api/reservation/{id}', name: 'api_reservation', methods: ['GET'])]
public function apiReservation(
    int $id,
    ReservationRepository $repo,
    SeanceRepository $seanceRepo
): JsonResponse {

    $reservation = $repo->find($id);

    if (!$reservation) {
        return new JsonResponse(['error' => 'Reservation not found'], 404);
    }

    $seance = $seanceRepo->find($reservation->getIdSeance());

    return new JsonResponse([
        'reservation_id' => $reservation->getId(),
        'statut' => $reservation->getStatut(),
        'seance' => $seance?->getTitre(),
        'date' => $seance?->getDateDebut()?->format('d/m/Y H:i'),
        'lieu' => $seance?->getLieu(),
    ]);
}
   #[Route('/reservations/annuler/{id}', name: 'reservation_annuler')]
public function annuler(
    Reservation $reservation,
    EntityManagerInterface $em,
    SessionInterface $session,
    SeanceRepository $seanceRepo
): Response {

    if (!$session->get('user_id')) {
        return $this->redirectToRoute('login');
    }

    $seance = $seanceRepo->find($reservation->getIdSeance());

    if (!$seance) {
        $this->addFlash('error', 'Séance introuvable');
        return $this->redirectToRoute('mes_reservations');
    }

    $now = new \DateTime();
    $seanceDate = $seance->getDateDebut();

    // ⚠️ sécurité si date invalide
    if (!$seanceDate) {
        $this->addFlash('error', 'Date de séance invalide');
        return $this->redirectToRoute('mes_reservations');
    }

    $hours = ($now->diff($seanceDate))->days * 24 + $now->diff($seanceDate)->h;

    if ($seanceDate > $now && $hours < 24) {
        $this->addFlash('error', 'Impossible d’annuler à moins de 24h.');
        return $this->redirectToRoute('mes_reservations');
    }

    $reservation->setStatut('annulée');
    $em->flush();

    $this->addFlash('success', 'Réservation annulée avec succès.');

    return $this->redirectToRoute('mes_reservations');
}
#[Route('/mes-reservations', name: 'mes_reservations')]

  public function mesReservations(ReservationRepository $repo, SeanceRepository $seanceRepo, SessionInterface $session): Response
{
    if (!$session->get('user_id')) return $this->redirectToRoute('login');

    $userId = $session->get('user_id');

    $reservations = $repo->findBy(['idUtilisateur' => $userId]);

    // 🔥 ICI ON CACHE LES ANNULÉES
    $reservations = array_filter($reservations, function ($r) {
        return $r->getStatut() !== 'annulée';
    });

    $seances = [];
    foreach ($reservations as $r) {
        $seances[$r->getId()] = $seanceRepo->find($r->getIdSeance());
    }

    return $this->render('client/mes_reservations.html.twig', [
        'reservations' => $reservations,
        'seances' => $seances,
    ]);
}
#[Route('/api/stats', name: 'api_stats')]
public function stats(ReservationRepository $repo): JsonResponse
{
    return new JsonResponse([
        'total' => $repo->count([]),
        'confirme' => $repo->count(['statut' => 'confirmée']),
        'attente' => $repo->count(['statut' => 'en attente']),
        'annule' => $repo->count(['statut' => 'annulée']),
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
        return $this->render('admin/reservation.html.twig', [
            'reservations' => $reservations,
            'seances' => $seances,
        ]);
    }
    #[Route('/reservation/view/{id}', name: 'reservation_view')]
public function viewReservation(
    int $id,
    ReservationRepository $repo,
    SeanceRepository $seanceRepo
): Response {

    $reservation = $repo->find($id);

    if (!$reservation) {
        throw $this->createNotFoundException();
    }

    $seance = $seanceRepo->find($reservation->getIdSeance());

    return $this->render('reservation/view.html.twig', [
        'reservation' => $reservation,
        'seance' => $seance
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

    // ──────────────────────────────────────────────
    // TICKET PDF RÉSERVATION
    // ──────────────────────────────────────────────
    #[Route('/reservations/ticket/{id}', name: 'reservation_ticket')]
    public function ticket(
        int $id,
        ReservationRepository $resRepo,
        SeanceRepository $seanceRepo,
        PdfService $pdfService,
            QrCodeService $qrCodeService,   // ⭐ AJOUT ICI

        SessionInterface $session
    ): Response {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');

        $reservation = $resRepo->find($id);
        if (!$reservation || $reservation->getIdUtilisateur() !== $session->get('user_id')) {
            throw $this->createNotFoundException('Ticket introuvable');
        }

        $seance = $seanceRepo->find($reservation->getIdSeance());
$qrData =
"🎟 RESERVATION\n" .
"ID: " . $reservation->getId() . "\n" .
"Client: " . $session->get('user_nom') . " " . $session->get('user_prenom') . "\n" .
"Seance: " . ($seance ? $seance->getTitre() : 'N/A') . "\n" .
"Date: " . ($seance ? $seance->getDateDebut()->format('d/m/Y H:i') : 'N/A') . "\n" .
"Lieu: " . ($seance ? $seance->getLieu() : 'N/A');

$qrCodeBase64 = $qrCodeService->generateBase64($qrData);
$pdfContent = $pdfService->generateTicketReservation(
    [
       'numero' => 'RES-' . str_pad((string)$reservation->getId(), 5, '0', STR_PAD_LEFT),
        'client' => $session->get('user_nom') . ' ' . $session->get('user_prenom'),
        'seance' => $seance ? $seance->getTitre() : 'N/A',
        'date' => $seance ? $seance->getDateDebut()->format('d/m/Y H:i') : 'N/A',
        'lieu' => $seance ? $seance->getLieu() : 'N/A',
        'therapeute' => 'Non assigné',
    ],
    $qrCodeBase64
);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition',
            'inline; filename="ticket_reservation_' . $reservation->getId() . '.pdf"'
        );


        return $response;
    }
}
