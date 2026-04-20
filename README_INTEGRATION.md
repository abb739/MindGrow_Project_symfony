# 🤖 Guide d'intégration — Fonctionnalités Avancées & IA
## Projet MindGrow — Symfony 6.4

---

## 📦 FICHIERS À COPIER DANS VOTRE PROJET

```
src/Service/GeminiService.php        → votre projet src/Service/
src/Service/WordFilterService.php    → votre projet src/Service/
src/Service/TranslationService.php   → votre projet src/Service/
src/Service/PdfService.php           → votre projet src/Service/
src/Controller/ChatbotController.php → votre projet src/Controller/
templates/components/_chatbot.html.twig → votre projet templates/components/
```

---

## ⚙️ ÉTAPE 1 — Variables d'environnement (.env)

Ajoutez dans votre `.env` :

```env
GEMINI_API_KEY=AIzaSyC4JuMu69yhcfQLITA0rKLKEKw-QEVl6N4
```
> Vous pouvez réutiliser la même clé que dans le projet JavaFX.

---

## ⚙️ ÉTAPE 2 — config/services.yaml

Ajoutez dans `config/services.yaml` sous `services:` :

```yaml
App\Service\GeminiService:
    arguments:
        $geminiApiKey: '%env(GEMINI_API_KEY)%'
```

---

## ⚙️ ÉTAPE 3 — Activer le chatbot dans base.html.twig

Juste avant `</body>`, ajoutez :

```twig
{{ include('components/_chatbot.html.twig') }}
```

---

## ⚙️ ÉTAPE 4 — PDF des reçus (AbonnementController)

Dans `AbonnementController.php`, ajoutez l'import :

```php
use App\Service\PdfService;
use Symfony\Component\HttpFoundation\Response;
```

Puis ajoutez cette route (copiez depuis PATCHES_CONTROLLERS.php) :

```php
#[Route('/abonnements/recu/{id}', name: 'abonnement_recu')]
public function recu(int $id, AchatRepository $achatRepo, PdfService $pdfService, SessionInterface $session): Response
{
    // ... (voir PATCHES_CONTROLLERS.php)
}
```

Dans votre template `abonnement_index.html.twig`, ajoutez un lien :

```twig
<a href="{{ path('abonnement_recu', {id: achat.id}) }}" target="_blank">
    📄 Télécharger le reçu
</a>
```

---

## ⚙️ ÉTAPE 5 — PDF des tickets (ReservationController)

Même démarche avec `reservation_ticket` (voir PATCHES_CONTROLLERS.php).

Dans `mes_reservations.html.twig` :

```twig
<a href="{{ path('reservation_ticket', {id: reservation.id}) }}" target="_blank">
    🎫 Mon ticket
</a>
```

---

## ⚙️ ÉTAPE 6 — Filtre de mots (AvisController)

Dans `AvisController.php`, injectez `WordFilterService` :

```php
use App\Service\WordFilterService;

// Dans la méthode new() :
public function new(Request $request, ..., WordFilterService $wordFilter): Response
{
    // ...
    if ($wordFilter->containsBadWords($commentaire)) {
        $this->addFlash('error', 'Commentaire inapproprié.');
        return $this->redirectToRoute('therapeutes');
    }
    // ... reste du code
}
```

---

## ⚙️ ÉTAPE 7 — Traduction (optionnel, dans TherapeuteController)

```php
use App\Service\TranslationService;

// Injectez dans la méthode index() et passez à la vue :
$translationService = new TranslationService($this->httpClient);
// Exemple : traduire un avis en anglais
$texteEN = $translationService->translate($avis->getCommentaire(), 'fr', 'en');
```

---

## 🗺️ RÉCAPITULATIF DES NOUVELLES ROUTES

| Route                                  | Méthode | Description                        |
|----------------------------------------|---------|------------------------------------|
| `/chatbot/message`                     | POST    | Chatbot général (Gemini)           |
| `/chatbot/recommander-therapeute`      | POST    | Recommandation thérapeute par IA   |
| `/chatbot/recommander-seance`          | POST    | Recommandation séance par IA       |
| `/abonnements/recu/{id}`               | GET     | Reçu PDF abonnement                |
| `/reservations/ticket/{id}`            | GET     | Ticket PDF réservation             |

---

## ✅ FONCTIONNALITÉS DÉJÀ PRÉSENTES dans votre projet web

- **Stripe** : déjà dans `composer.json` + `.env` ✓
- **DomPDF** : déjà dans `composer.json` ✓  
- **Symfony Mailer** : déjà configuré ✓
- **QR Code** : `endroid/qr-code` déjà installé ✓ (peut être ajouté aux tickets PDF)
