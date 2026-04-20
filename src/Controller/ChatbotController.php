<?php

namespace App\Controller;

use App\Repository\AbonnementRepository;
use App\Repository\AchatRepository;
use App\Repository\ProgrammeRepository;
use App\Repository\SeanceRepository;
use App\Repository\TherapeuteRepository;
use App\Service\GeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    // ──────────────────────────────────────────────
    // CHATBOT GÉNÉRAL (programmes + séances)
    // Route : POST /chatbot/message
    // ──────────────────────────────────────────────
    #[Route('/chatbot/message', name: 'chatbot_message', methods: ['POST'])]
    public function message(
        Request            $request,
        GeminiService      $gemini,
        ProgrammeRepository $progRepo,
        SeanceRepository   $seanceRepo,
        SessionInterface   $session
    ): JsonResponse {
        if (!$session->get('user_id')) {
            return $this->json(['error' => 'Non connecté'], 401);
        }

        $data    = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if (empty($message)) {
            return $this->json(['error' => 'Message vide'], 400);
        }

        $programmes = $progRepo->findAll();
        $seances    = $seanceRepo->findAll();

        $response = $gemini->chatGeneral($message, $programmes, $seances);

        return $this->json(['response' => $response]);
    }
#[Route('/chatbot/seance', name: 'chatbot_seance', methods: ['POST'])]
public function chatSeance(
    Request $request,
    GeminiService $gemini,
    SeanceRepository $seanceRepo,
    SessionInterface $session
): JsonResponse {

    if (!$session->get('user_id')) {
        return $this->json(['error' => 'Non connecté'], 401);
    }

    $data = json_decode($request->getContent(), true);
    $message = trim($data['message'] ?? '');

    if (empty($message)) {
        return $this->json(['error' => 'Message vide'], 400);
    }

    $seances = $seanceRepo->findAll();

    $response = $gemini->chatSeanceSpecial($message, $seances);

    return $this->json(['response' => $response]);
}
    // ──────────────────────────────────────────────
    // RECOMMANDATION THÉRAPEUTE par IA
    // Route : POST /chatbot/recommander-therapeute
    // ──────────────────────────────────────────────
    #[Route('/chatbot/recommander-therapeute', name: 'chatbot_recommander_therapeute', methods: ['POST'])]
    public function recommanderTherapeute(
        Request              $request,
        GeminiService        $gemini,
        TherapeuteRepository $therapeuteRepo,
        SessionInterface     $session
    ): JsonResponse {
        if (!$session->get('user_id')) {
            return $this->json(['error' => 'Non connecté'], 401);
        }

        $data    = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if (empty($message)) {
            return $this->json(['error' => 'Décrivez vos besoins'], 400);
        }

        $therapeutes = $therapeuteRepo->findAll();
        $response    = $gemini->recommanderTherapeute($message, $therapeutes);

        return $this->json(['response' => $response]);
    }

    // ──────────────────────────────────────────────
    // RECOMMANDATION SÉANCE par IA
    // Route : POST /chatbot/recommander-seance
    // ──────────────────────────────────────────────
    #[Route('/chatbot/recommander-seance', name: 'chatbot_recommander_seance', methods: ['POST'])]
    public function recommanderSeance(
        Request          $request,
        GeminiService    $gemini,
        SeanceRepository $seanceRepo,
        SessionInterface $session
    ): JsonResponse {
        if (!$session->get('user_id')) {
            return $this->json(['error' => 'Non connecté'], 401);
        }

        $data    = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if (empty($message)) {
            return $this->json(['error' => 'Message vide'], 400);
        }

        $seances  = $seanceRepo->findAll();
        $response = $gemini->recommanderSeance($message, $seances);

        return $this->json(['response' => $response]);
    }

    // ──────────────────────────────────────────────
    // CHATBOT ABONNEMENT par IA
    // Route : POST /chatbot/abonnement
    // ──────────────────────────────────────────────
    #[Route('/chatbot/abonnement', name: 'chatbot_abonnement', methods: ['POST'])]
    public function chatAbonnement(
        Request               $request,
        GeminiService         $gemini,
        AbonnementRepository  $abonnementRepo,
        AchatRepository       $achatRepo,
        SessionInterface      $session
    ): JsonResponse {
        if (!$session->get('user_id')) {
            return $this->json(['error' => 'Non connecté'], 401);
        }

        $data    = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if (empty($message)) {
            return $this->json(['error' => 'Message vide'], 400);
        }

        $userId      = $session->get('user_id');
        $abonnements = $abonnementRepo->findAll();

        // Récupérer l'abonnement actif de l'utilisateur pour personnaliser les réponses
        $achatActif    = $achatRepo->findAbonnementActifByUser($userId);
        $achatActifData = null;
        if ($achatActif) {
            $abo = $abonnementRepo->find($achatActif->getIdAbonnement());
            $achatActifData = [
                'nom'             => $abo?->getNom() ?? '—',
                'date_achat'      => $achatActif->getDateAchat()?->format('d/m/Y') ?? '—',
                'date_expiration' => $achatActif->getDateAchat()
                    ? (clone $achatActif->getDateAchat())
                        ->modify('+' . ($abo?->getDureeMois() ?? 1) . ' months')
                        ->format('d/m/Y')
                    : '—',
            ];
        }

        $response = $gemini->chatAbonnement($message, $abonnements, $achatActifData);

        return $this->json(['response' => $response]);
    }
}