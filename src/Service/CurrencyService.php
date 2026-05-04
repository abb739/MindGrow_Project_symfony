<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

/**
 * CurrencyService — Métier avancé de gestion des devises
 *
 * Fonctionnalités :
 *  - Récupération des taux en temps réel (open.er-api.com, gratuit, sans clé)
 *  - Cache Symfony (TTL configurable, par défaut 6h)
 *  - Fallback sur taux statiques si l'API est indisponible
 *  - Conversion TND → n'importe quelle devise
 *  - Formatage localisé des montants
 *  - Historique de conversion (log)
 */
class CurrencyService
{
    // ──────────────────────────────────────────────────────────────
    // CONSTANTES
    // ──────────────────────────────────────────────────────────────

    /** Durée de vie du cache des taux (en secondes) */
    private const CACHE_TTL = 21_600; // 6 heures

    /** Clé de cache */
    private const CACHE_KEY = 'currency_rates_tnd';

    /** URL de l'API de taux de change (gratuite, sans clé) */
    private const API_URL = 'https://open.er-api.com/v6/latest/TND';

    /** Devises supportées avec leurs métadonnées */
    public const DEVISES = [
        'TND' => ['nom' => 'Dinar Tunisien',   'symbole' => 'TND', 'flag' => '🇹🇳', 'locale' => 'ar_TN', 'decimales' => 3],
        'EUR' => ['nom' => 'Euro',              'symbole' => '€',   'flag' => '🇪🇺', 'locale' => 'fr_FR', 'decimales' => 2],
        'USD' => ['nom' => 'Dollar US',         'symbole' => '$',   'flag' => '🇺🇸', 'locale' => 'en_US', 'decimales' => 2],
        'GBP' => ['nom' => 'Livre Sterling',    'symbole' => '£',   'flag' => '🇬🇧', 'locale' => 'en_GB', 'decimales' => 2],
        'SAR' => ['nom' => 'Riyal Saoudien',    'symbole' => '﷼',   'flag' => '🇸🇦', 'locale' => 'ar_SA', 'decimales' => 2],
        'MAD' => ['nom' => 'Dirham Marocain',   'symbole' => 'MAD', 'flag' => '🇲🇦', 'locale' => 'fr_MA', 'decimales' => 2],
        'CAD' => ['nom' => 'Dollar Canadien',   'symbole' => 'CA$', 'flag' => '🇨🇦', 'locale' => 'fr_CA', 'decimales' => 2],
        'JPY' => ['nom' => 'Yen Japonais',      'symbole' => '¥',   'flag' => '🇯🇵', 'locale' => 'ja_JP', 'decimales' => 0],
        'CHF' => ['nom' => 'Franc Suisse',      'symbole' => 'CHF', 'flag' => '🇨🇭', 'locale' => 'de_CH', 'decimales' => 2],
        'AED' => ['nom' => 'Dirham EAU',        'symbole' => 'AED', 'flag' => '🇦🇪', 'locale' => 'ar_AE', 'decimales' => 2],
    ];

    /** Taux de fallback (statiques, si API indisponible) */
    private const TAUX_FALLBACK = [
        'TND' => 1.0,
        'EUR' => 0.2965,
        'USD' => 0.3200,
        'GBP' => 0.2540,
        'SAR' => 1.2005,
        'MAD' => 3.2100,
        'CAD' => 0.4390,
        'JPY' => 47.850,
        'CHF' => 0.2890,
        'AED' => 1.1750,
    ];

    // ──────────────────────────────────────────────────────────────
    // CONSTRUCTEUR
    // ──────────────────────────────────────────────────────────────

    public function __construct(
        private readonly CacheInterface  $cache,
        private readonly LoggerInterface $logger,
    ) {}

    // ──────────────────────────────────────────────────────────────
    // 1. RÉCUPÉRATION DES TAUX
    // ──────────────────────────────────────────────────────────────

    /**
     * Retourne tous les taux de change (base TND).
     * Utilise le cache Symfony pour éviter les appels API répétés.
     *
     * @return array{rates: array<string,float>, source: string, updated_at: string}
     */
    public function getTaux(): array
    {
        try {
            return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
                $item->expiresAfter(self::CACHE_TTL);
                return $this->fetchTauxFromApi();
            });
        } catch (\Throwable $e) {
            $this->logger->warning('[CurrencyService] Cache indisponible, fallback utilisé.', ['error' => $e->getMessage()]);
            return $this->buildFallbackResponse();
        }
    }

    /**
     * Retourne le taux de change TND → $devise.
     */
    public function getTaux1(string $devise): float
    {
        $devise = strtoupper($devise);
        $data   = $this->getTaux();
        return $data['rates'][$devise] ?? self::TAUX_FALLBACK[$devise] ?? 1.0;
    }

    /**
     * Invalide le cache pour forcer un rechargement des taux.
     */
    public function invaliderCache(): void
    {
        $this->cache->delete(self::CACHE_KEY);
        $this->logger->info('[CurrencyService] Cache des taux invalidé.');
    }

    // ──────────────────────────────────────────────────────────────
    // 2. CONVERSION
    // ──────────────────────────────────────────────────────────────

    /**
     * Convertit un montant TND vers la devise cible.
     *
     * @param float  $montantTND Montant en dinars tunisiens
     * @param string $devise     Code ISO de la devise cible (ex: 'EUR')
     * @return float             Montant converti
     */
    public function convertir(float $montantTND, string $devise): float
    {
        $devise = strtoupper($devise);

        if ($devise === 'TND') {
            return round($montantTND, 3);
        }

        $taux = $this->getTaux1($devise);
        return round($montantTND * $taux, self::DEVISES[$devise]['decimales'] ?? 2);
    }

    /**
     * Convertit un montant TND vers plusieurs devises en une seule fois.
     *
     * @param float    $montantTND
     * @param string[] $devises    Liste de codes ISO
     * @return array<string, float>
     */
    public function convertirMultiple(float $montantTND, array $devises): array
    {
        $result = [];
        foreach ($devises as $devise) {
            $result[strtoupper($devise)] = $this->convertir($montantTND, $devise);
        }
        return $result;
    }

    // ──────────────────────────────────────────────────────────────
    // 3. FORMATAGE
    // ──────────────────────────────────────────────────────────────

    /**
     * Retourne un montant TND formaté dans la devise cible.
     * Exemple : formater(100.0, 'EUR') → "29,65 €"
     */
    public function formater(float $montantTND, string $devise, bool $avecSymbole = true): string
    {
        $devise   = strtoupper($devise);
        $montant  = $this->convertir($montantTND, $devise);
        $meta     = self::DEVISES[$devise] ?? ['symbole' => $devise, 'decimales' => 2];
        $decimales = $meta['decimales'];

        $formatted = number_format($montant, $decimales, ',', ' ');

        if (!$avecSymbole) {
            return $formatted;
        }

        $symbole = $meta['symbole'];

        // Positionnement du symbole selon la devise
        return in_array($devise, ['EUR', 'GBP', 'CHF'])
            ? $formatted . ' ' . $symbole
            : $symbole . ' ' . $formatted;
    }

    /**
     * Retourne la liste des devises disponibles pour un <select> HTML.
     *
     * @return array<string, string>  ['EUR' => '🇪🇺 Euro (EUR)', ...]
     */
    public function getOptionsList(): array
    {
        $options = [];
        foreach (self::DEVISES as $code => $meta) {
            $options[$code] = $meta['flag'] . ' ' . $meta['nom'] . ' (' . $code . ')';
        }
        return $options;
    }

    // ──────────────────────────────────────────────────────────────
    // 4. INFORMATIONS SUR LES TAUX
    // ──────────────────────────────────────────────────────────────

    /**
     * Retourne les métadonnées d'une devise.
     */
    public function getMetaDevise(string $devise): array
    {
        return self::DEVISES[strtoupper($devise)] ?? [];
    }

    /**
     * Vérifie si une devise est supportée.
     */
    public function estSupportee(string $devise): bool
    {
        return isset(self::DEVISES[strtoupper($devise)]);
    }

    /**
     * Retourne un résumé des taux pour la vue (template Twig / JSON).
     * Inclut taux, symbole, flag, source, date de mise à jour.
     */
    public function getTauxPourVue(): array
    {
        $data   = $this->getTaux();
        $result = [];

        foreach (self::DEVISES as $code => $meta) {
            $result[$code] = [
                'taux'       => $data['rates'][$code] ?? self::TAUX_FALLBACK[$code],
                'symbole'    => $meta['symbole'],
                'flag'       => $meta['flag'],
                'nom'        => $meta['nom'],
                'decimales'  => $meta['decimales'],
            ];
        }

        return [
            'devises'    => $result,
            'source'     => $data['source'],
            'updated_at' => $data['updated_at'],
            'base'       => 'TND',
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // MÉTHODES PRIVÉES
    // ──────────────────────────────────────────────────────────────

    /**
     * Appel HTTP à l'API de taux de change.
     */
    private function fetchTauxFromApi(): array
    {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 5,
                'header'  => "Accept: application/json\r\nUser-Agent: MindGrow/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
            ],
        ]);

        $raw = @file_get_contents(self::API_URL, false, $ctx);

        if ($raw === false) {
            $this->logger->warning('[CurrencyService] API inaccessible, fallback utilisé.');
            return $this->buildFallbackResponse();
        }

        $json = json_decode($raw, true);

        if (!isset($json['rates']) || $json['result'] !== 'success') {
            $this->logger->warning('[CurrencyService] Réponse API invalide.', ['raw' => substr($raw, 0, 200)]);
            return $this->buildFallbackResponse();
        }

        // Filtre uniquement les devises supportées
        $rates = [];
        foreach (array_keys(self::DEVISES) as $code) {
            $rates[$code] = (float) ($json['rates'][$code] ?? self::TAUX_FALLBACK[$code]);
        }
        $rates['TND'] = 1.0;

        $this->logger->info('[CurrencyService] Taux rechargés depuis l\'API.', ['devises' => array_keys($rates)]);

        return [
            'rates'      => $rates,
            'source'     => 'live',
            'updated_at' => $json['time_last_update_utc'] ?? date('c'),
        ];
    }

    /**
     * Construit la réponse de fallback avec les taux statiques.
     */
    private function buildFallbackResponse(): array
    {
        return [
            'rates'      => self::TAUX_FALLBACK,
            'source'     => 'fallback',
            'updated_at' => date('c'),
        ];
    }
}
