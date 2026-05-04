<?php

namespace App\Service;

/**
 * Service IA — Google Gemini (auto-détection du modèle disponible)
 * Utilise cURL natif PHP avec CURLOPT_NOPROXY pour bypass le proxy XAMPP
 */
class GeminiService
{
    // Modèles par ordre de priorité — gemini-flash-latest confirmé par Google AI Studio
    private const MODELS = [
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent',
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite:generateContent',
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent',
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-8b:generateContent',
    ];

    private ?string $workingUrl = null;

    public function __construct(private string $geminiApiKey)
    {
    }

    // ──────────────────────────────────────────────
    // 1. CHATBOT GÉNÉRAL
    // ──────────────────────────────────────────────
    public function chatGeneral(string $userMessage, array $programmes = [], array $seances = []): string
    {
        $ctx  = "Tu es un assistant IA bienveillant de MindGrow. Réponds en français, de façon naturelle.\n\n";

        if ($programmes) {
            $ctx .= "Programmes disponibles :\n";
            foreach ($programmes as $p) {
                $ctx .= "- {$p->getTitre()}: {$p->getDescription()}\n";
            }
        }
        if ($seances) {
            $ctx .= "\nSéances disponibles :\n";
            foreach ($seances as $s) {
                $ctx .= "- {$s->getTitre()} le {$s->getDateDebut()->format('d/m/Y')} à {$s->getLieu()}\n";
            }
        }
        $ctx .= "\nRéponds à toutes les questions, même hors contexte MindGrow.";

        return $this->call($ctx, $userMessage);
    }

    // ──────────────────────────────────────────────
    // CHATBOT PROGRAMME
    // ──────────────────────────────────────────────
    public function chatProgramme(string $userMessage, array $programmes, array $categories = [], ?array $programmeActuel = null): string
    {
        $ctx  = "Tu es un coach bien-être IA de MindGrow, expert en programmes de santé et bien-être.\n";
        $ctx .= "Réponds en français, de façon courte, structurée et bienveillante.\n\n";

        if ($programmeActuel) {
            $ctx .= "Programme actuellement consulté :\n";
            $ctx .= "- Titre : {$programmeActuel['titre']}\n";
            $ctx .= "- Catégorie : {$programmeActuel['categorie']}\n";
            $ctx .= "- Description : {$programmeActuel['description']}\n\n";
        }

        if ($programmes) {
            $ctx .= "Tous les programmes disponibles :\n";
            foreach ($programmes as $p) {
                $catNom = $categories[$p->getIdCategorie()] ?? 'N/A';
                $ctx .= "- #{$p->getId()} {$p->getTitre()} (Catégorie: {$catNom}) : {$p->getDescription()}\n";
            }
        }

        $ctx .= "\nIMPORTANT :\n";
        $ctx .= "- Réponds uniquement sur les programmes MindGrow\n";
        $ctx .= "- Recommande des programmes selon les besoins\n";
        $ctx .= "- Sois concis (max 4 lignes par réponse)\n";
        $ctx .= "- Format clair avec emojis\n";

        return $this->call($ctx, $userMessage);
    }

    // ──────────────────────────────────────────────
    // 2. RECOMMANDATION THÉRAPEUTE
    // ──────────────────────────────────────────────
    public function recommanderTherapeute(string $userInput, array $therapeutes): string
    {
        $ctx  = "Tu es un expert en orientation thérapeutique pour MindGrow.\n";
        $ctx .= "Analyse les besoins et recommande 1-2 thérapeutes adaptés. Sois empathique et concis.\n\n";
        $ctx .= "Thérapeutes disponibles :\n";
        foreach ($therapeutes as $t) {
            $ctx .= "- {$t->getNom()} {$t->getPrenom()} (Spécialité: {$t->getSpecialite()})\n";
        }
        $ctx .= "\nExplique pourquoi chaque thérapeute recommandé convient.";

        return $this->call($ctx, $userInput);
    }
public function chatSeanceSpecial(string $userInput, array $seances): string
{
    $ctx = "Tu es un coach spécialisé uniquement dans les séances de bien-être (yoga, méditation, relaxation).

IMPORTANT :
- Réponds uniquement sur les séances
- Réponse courte
- Structurée
- PAS de paragraphe long

FORMAT :

🧘 Séance conseillée :
- Nom : ...
- Lieu : ...

📍 Pourquoi :
- raison 1
- raison 2

💡 Séances disponibles :
";

    foreach ($seances as $s) {
        $ctx .= "- {$s->getTitre()} ({$s->getLieu()}): {$s->getDescription()}\n";
    }

    return $this->call($ctx, $userInput);
}
    // ──────────────────────────────────────────────
    // 3. RECOMMANDATION SÉANCE
    // ──────────────────────────────────────────────
    public function recommanderSeance(string $userInput, array $seances): string
    {
        $ctx  = "Tu es un expert bien-être MindGrow. Recommande des séances selon le ressenti. Sois motivant.\n\n";
        $ctx .= "Séances disponibles :\n";
        foreach ($seances as $s) {
            $ctx .= "- {$s->getTitre()} ({$s->getLieu()}, {$s->getDateDebut()->format('d/m/Y')}): {$s->getDescription()}\n";
        }
        $ctx .= "\nSélectionne 1-3 séances, explique pourquoi, termine par un encouragement.";

        return $this->call($ctx, $userInput);
    }

    // ──────────────────────────────────────────────
    // 4. CHATBOT ABONNEMENT & PAIEMENT — Avancé
    // ──────────────────────────────────────────────
    public function chatAbonnement(string $userMessage, array $abonnements, ?array $achatActif = null): string
    {
        // ── Contexte enrichi sur MindGrow ──
        $ctx  = <<<PROMPT
Tu es un conseiller expert en abonnements et paiements pour MindGrow, une plateforme de bien-être mental (méditation, yoga, thérapie).
Tu parles français, tu es chaleureux, professionnel et précis.
Tu connais parfaitement les abonnements, les prix, les méthodes de paiement et les avantages de chaque plan.

RÈGLES IMPORTANTES :
- Réponds TOUJOURS en français
- Sois précis sur les prix et durées
- Si l'utilisateur mentionne un budget, recommande le meilleur rapport qualité-prix
- Pour les questions de paiement : MindGrow accepte les cartes bancaires (Visa, Mastercard) via Stripe (paiement sécurisé 3D Secure)
- En cas d'annulation : l'abonnement reste actif jusqu'à la fin de la période payée
- Les paiements sont en TND (Dinar Tunisien) mais affichables en EUR, USD, GBP et d'autres devises
- Ne jamais inventer de prix ou d'abonnements qui ne sont pas dans la liste ci-dessous

PROMPT;

        // ── Liste des abonnements disponibles ──
        $ctx .= "
📋 ABONNEMENTS DISPONIBLES :
";
        foreach ($abonnements as $a) {
            $prixMois = $a->getDureeMois() > 1
                ? sprintf(' (soit %.2f TND/mois)', $a->getPrix() / $a->getDureeMois())
                : '';
            $ctx .= sprintf(
                "• %s — %.2f TND%s — Durée : %d mois
  Description : %s
",
                $a->getNom(),
                $a->getPrix(),
                $prixMois,
                $a->getDureeMois(),
                $a->getDescription() ?? 'Accès complet à toutes les fonctionnalités MindGrow.'
            );
        }

        // ── Abonnement actif de l'utilisateur si disponible ──
        if ($achatActif) {
            $ctx .= "
✅ L'UTILISATEUR A DÉJÀ UN ABONNEMENT ACTIF :
";
            $ctx .= sprintf(
                "Plan actuel : %s — Souscrit le : %s — Expire le : %s
",
                $achatActif['nom'] ?? '—',
                $achatActif['date_achat'] ?? '—',
                $achatActif['date_expiration'] ?? '—'
            );
        }

        // ── Guide de réponse selon l'intention ──
        $ctx .= <<<GUIDE

🎯 GUIDE DE RÉPONSE SELON L'INTENTION DÉTECTÉE :

Si l'utilisateur demande QUEL abonnement choisir / recommandation :
→ Analyse son budget ou ses besoins, puis recommande 1 ou 2 plans avec une explication claire des avantages.

Si l'utilisateur demande le prix / combien ça coûte :
→ Donne le prix exact en TND avec le prix mensuel équivalent pour les offres longue durée.

Si l'utilisateur demande COMMENT payer :
→ Explique : paiement par carte bancaire (Visa/Mastercard), 100% sécurisé via Stripe, avec confirmation par email immédiate.

Si l'utilisateur demande s'il peut ANNULER ou REMBOURSER :
→ Explique que l'abonnement reste actif jusqu'à la fin de la période, pas de remboursement partiel, mais pas de renouvellement automatique.

Si l'utilisateur demande QUEL EST LE PLUS POPULAIRE ou LE PLUS ACHETÉ :
→ Indique que les plans 6 et 12 mois sont les plus plébiscités pour le rapport qualité-prix.

Si l'utilisateur veut COMPARER des abonnements :
→ Fais un comparatif clair : prix total, prix/mois, durée, avantages.

Réponds de façon naturelle, sans répéter le contexte. Sois direct et utile.

CONTRAINTES DE FORMAT :
- Réponse COMPLÈTE obligatoire — ne jamais couper une phrase en plein milieu
- Maximum 4-5 phrases par réponse, claires et concises
- Si tu recommandes un abonnement, donne le nom + prix + durée en une ligne
- Pas de listes à puces sauf pour comparer 2+ abonnements
- Termine TOUJOURS par une phrase complète avec un point final
GUIDE;

        return $this->call($ctx, $userMessage);
    }

    // ──────────────────────────────────────────────
    // APPEL API — Auto-détection du bon modèle
    // ──────────────────────────────────────────────
    private function call(string $context, string $userInput): string
    {
        $payload = json_encode([
            'contents' => [[
                'parts' => [['text' => $context . "\n\nMessage : " . $userInput]]
            ]],
            'generationConfig' => [
                'maxOutputTokens' => 2048,
                'temperature'     => 0.5,
                'topP'            => 0.9,
            ],
        ]);

        // Toujours tester tous les modèles pour trouver un qui répond
        $lastError = '';
        foreach (self::MODELS as $url) {
            [$result, $code] = $this->httpPost($url, $payload);

            if ($code === 200) {
                return $result; // ✅ succès
            }

            // Modèle non trouvé → essayer le suivant
            if ($code === 404 || str_contains($result, 'not found for API version') ||
                str_contains($result, 'not supported for generateContent')) {
                continue;
            }

            // Quota dépassé → essayer le modèle suivant (quota indépendant par modèle)
            if ($code === 429) {
                $lastError = '⚠️ Quota IA atteint. Créez une nouvelle clé sur https://aistudio.google.com/app/apikey et mettez-la dans .env (GEMINI_API_KEY=...)';
                continue;
            }

            // Autres erreurs → stopper
            $lastError = $result;
            break;
        }

        return $lastError ?: '⚠️ Aucun modèle Gemini disponible. Vérifiez votre GEMINI_API_KEY dans .env';
    }

    private function httpPost(string $url, string $payload): array
    {
        $fullUrl = $url . '?key=' . $this->geminiApiKey;

        $ch = curl_init($fullUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_PROXY          => '',   // bypass proxy XAMPP
            CURLOPT_NOPROXY        => '*',  // connexion directe
        ]);

        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['⚠️ Connexion impossible à l\'API Gemini.', 0];
        }

        if ($httpCode !== 200) {
            $decoded = json_decode($body, true);
            $msg = $decoded['error']['message'] ?? ('Erreur code ' . $httpCode);
            return [$msg, $httpCode];
        }

        $data = json_decode($body, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Aucune réponse.';
        return [$text, 200];
    }
}
