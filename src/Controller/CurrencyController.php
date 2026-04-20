<?php

namespace App\Controller;

use App\Service\CurrencyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * CurrencyController — API interne de gestion des devises
 *
 * Routes :
 *   GET  /api/currency/rates              → tous les taux actuels
 *   GET  /api/currency/convert            → conversion d'un montant
 *   POST /api/currency/convert/batch      → conversion multiple
 *   POST /api/currency/cache/refresh      → forcer rechargement des taux (admin)
 */
#[Route('/api/currency', name: 'api_currency_')]
class CurrencyController extends AbstractController
{
    public function __construct(
        private readonly CurrencyService $currencyService,
    ) {}

    // ──────────────────────────────────────────────────────────────
    // 1. TOUS LES TAUX
    // ──────────────────────────────────────────────────────────────

    /**
     * Retourne tous les taux de change (base TND) avec métadonnées.
     *
     * GET /api/currency/rates
     *
     * Réponse JSON :
     * {
     *   "success": true,
     *   "base": "TND",
     *   "source": "live" | "fallback",
     *   "updated_at": "...",
     *   "devises": {
     *     "EUR": { "taux": 0.2965, "symbole": "€", "flag": "🇪🇺", "nom": "Euro", "decimales": 2 },
     *     ...
     *   }
     * }
     */
    #[Route('/rates', name: 'rates', methods: ['GET'])]
    public function rates(): JsonResponse
    {
        try {
            $data = $this->currencyService->getTauxPourVue();

            return $this->json([
                'success'    => true,
                'base'       => $data['base'],
                'source'     => $data['source'],
                'updated_at' => $data['updated_at'],
                'devises'    => $data['devises'],
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error'   => 'Impossible de récupérer les taux.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // 2. CONVERSION UNITAIRE
    // ──────────────────────────────────────────────────────────────

    /**
     * Convertit un montant TND vers une devise cible.
     *
     * GET /api/currency/convert?montant=100&devise=EUR
     *
     * Réponse JSON :
     * {
     *   "success": true,
     *   "montant_tnd": 100.0,
     *   "devise": "EUR",
     *   "montant_converti": 29.65,
     *   "montant_formate": "29,65 €",
     *   "taux": 0.2965
     * }
     */
    #[Route('/convert', name: 'convert', methods: ['GET'])]
    public function convert(Request $request): JsonResponse
    {
        $montantRaw = $request->query->get('montant');
        $devise     = strtoupper((string) $request->query->get('devise', 'EUR'));

        // Validation
        if ($montantRaw === null || !is_numeric($montantRaw)) {
            return $this->json([
                'success' => false,
                'error'   => 'Paramètre "montant" manquant ou invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->currencyService->estSupportee($devise)) {
            return $this->json([
                'success' => false,
                'error'   => sprintf('Devise "%s" non supportée.', $devise),
                'devises_supportees' => array_keys(CurrencyService::DEVISES),
            ], Response::HTTP_BAD_REQUEST);
        }

        $montant  = (float) $montantRaw;
        $converti = $this->currencyService->convertir($montant, $devise);
        $formate  = $this->currencyService->formater($montant, $devise);
        $taux     = $this->currencyService->getTaux1($devise);

        return $this->json([
            'success'          => true,
            'montant_tnd'      => $montant,
            'devise'           => $devise,
            'montant_converti' => $converti,
            'montant_formate'  => $formate,
            'taux'             => $taux,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // 3. CONVERSION BATCH (plusieurs devises d'un coup)
    // ──────────────────────────────────────────────────────────────

    /**
     * Convertit un montant TND vers plusieurs devises en une seule requête.
     *
     * POST /api/currency/convert/batch
     * Body JSON : { "montant": 100, "devises": ["EUR", "USD", "GBP"] }
     *
     * Réponse JSON :
     * {
     *   "success": true,
     *   "montant_tnd": 100.0,
     *   "conversions": {
     *     "EUR": { "montant": 29.65, "formate": "29,65 €", "taux": 0.2965 },
     *     "USD": { "montant": 32.00, "formate": "$ 32,00", "taux": 0.32 }
     *   }
     * }
     */
    #[Route('/convert/batch', name: 'convert_batch', methods: ['POST'])]
    public function convertBatch(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);

        if (!isset($body['montant']) || !is_numeric($body['montant'])) {
            return $this->json([
                'success' => false,
                'error'   => 'Champ "montant" manquant ou invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $montant = (float) $body['montant'];
        $devises = $body['devises'] ?? array_keys(CurrencyService::DEVISES);

        // Filtre les devises non supportées
        $devises = array_filter(
            array_map('strtoupper', (array) $devises),
            fn(string $d) => $this->currencyService->estSupportee($d)
        );

        $conversions = [];
        foreach ($devises as $devise) {
            $conversions[$devise] = [
                'montant' => $this->currencyService->convertir($montant, $devise),
                'formate' => $this->currencyService->formater($montant, $devise),
                'taux'    => $this->currencyService->getTaux1($devise),
            ];
        }

        return $this->json([
            'success'     => true,
            'montant_tnd' => $montant,
            'conversions' => $conversions,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // 4. RAFRAÎCHISSEMENT DU CACHE (admin uniquement)
    // ──────────────────────────────────────────────────────────────

    /**
     * Force le rechargement des taux depuis l'API externe.
     * Réservé aux administrateurs.
     *
     * POST /api/currency/cache/refresh
     */
    #[Route('/cache/refresh', name: 'cache_refresh', methods: ['POST'])]
    public function refreshCache(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $this->currencyService->invaliderCache();

        // Recharge immédiatement les nouveaux taux
        $data = $this->currencyService->getTauxPourVue();

        return $this->json([
            'success'    => true,
            'message'    => 'Cache des taux rechargé avec succès.',
            'source'     => $data['source'],
            'updated_at' => $data['updated_at'],
        ]);
    }
}
