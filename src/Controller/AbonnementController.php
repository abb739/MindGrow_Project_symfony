<?php
namespace App\Controller;

use App\Entity\Abonnement;
use App\Entity\Achat;
use App\Repository\AbonnementRepository;
use App\Repository\AchatRepository;
use App\Service\CurrencyService;
use App\Service\EmailService;
use App\Service\PdfService;
use App\Service\QrCodeService;
use App\Repository\UtilisateurRepository;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\CardException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class AbonnementController extends AbstractController
{
    // ============================================================
    // FRONTOFFICE CLIENT
    // ============================================================

    #[Route('/abonnements', name: 'abonnements')]
    public function index(
        AbonnementRepository $repo,
        AchatRepository $achatRepo,
        SessionInterface $session,
        Request $request,
        CurrencyService $currencyService
    ): Response {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');

        $q     = trim($request->query->get('q', ''));
        $duree = (int)$request->query->get('duree', 0);
        $tri   = $request->query->get('tri', 'populaire');

        $filtreActif = ($q !== '' || $duree > 0 || $tri !== 'populaire');
        $abonnements = $filtreActif
            ? $repo->findByFilters($q, $duree, $tri)
            : $repo->findAllOrderedByPrix();

        $userId     = $session->get('user_id');
        $achatActif = $achatRepo->findAbonnementActifByUser($userId);
        $stats      = $achatRepo->getStatsAbonnements();

        return $this->render('client/abonnement_index.html.twig', [
            'abonnements'      => $abonnements,
            'achatActif'       => $achatActif,
            'stats'            => $stats,
            'currency_options' => $currencyService->getOptionsList(),
        ]);
    }

    #[Route('/abonnements/paiement/{id}', name: 'abonnement_paiement')]
    public function paiement(Abonnement $abonnement, AchatRepository $achatRepo, SessionInterface $session): Response
    {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');

        $dateExpiration = (new \DateTime())->modify('+' . $abonnement->getDureeMois() . ' months');

        return $this->render('client/abonnement_paiement.html.twig', [
            'abonnement'        => $abonnement,
            'dateExpiration'    => $dateExpiration,
            'stripe_public_key' => $this->getParameter('stripe_public_key'),
            'errors'            => [],
        ]);
    }

    #[Route('/abonnements/payer/{id}', name: 'abonnement_payer', methods: ['POST'])]
    public function payer(
        Request $request,
        Abonnement $abonnement,
        EntityManagerInterface $em,
        AchatRepository $achatRepo,
        SessionInterface $session,
        EmailService $emailService,
        QrCodeService $qrService,
        UtilisateurRepository $userRepo
    ): Response {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');

        $stripeToken = trim($request->request->get('stripeToken', ''));
        $cardHolder  = trim($request->request->get('cardHolder', ''));
        $errors      = [];
        $dateExp     = (new \DateTime())->modify('+' . $abonnement->getDureeMois() . ' months');

        if (empty($stripeToken)) {
            $errors[] = 'Token de paiement manquant. Veuillez réessayer.';
        }
        if (empty($cardHolder) || strlen($cardHolder) < 2) {
            $errors[] = 'Le nom du titulaire est obligatoire.';
        }

        if (!empty($errors)) {
            return $this->render('client/abonnement_paiement.html.twig', [
                'abonnement'      => $abonnement,
                'dateExpiration'  => $dateExp,
                'errors'          => $errors,
                'cardHolder'      => $cardHolder,
                'stripe_public_key' => $this->getParameter('stripe_public_key'),
            ]);
        }

        $userId = $session->get('user_id');

        // ── Stripe : charger via le token créé par Stripe.js ──
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        try {
            $charge = \Stripe\Charge::create([
                'amount'      => (int) round((float)$abonnement->getPrix() * 100),
                'currency'    => 'eur',   // TND non supporté en mode test Stripe
                'source'      => $stripeToken,
                'description' => 'MindGrow — ' . $abonnement->getNom(),
                'metadata'    => [
                    'abonnement' => $abonnement->getNom(),
                    'user_id'    => $userId,
                    'duree_mois' => $abonnement->getDureeMois(),
                ],
            ]);

            if ($charge->status !== 'succeeded') {
                $errors[] = 'Paiement non confirmé. Veuillez réessayer.';
                return $this->render('client/abonnement_paiement.html.twig', [
                    'abonnement'        => $abonnement,
                    'dateExpiration'    => $dateExp,
                    'errors'            => $errors,
                    'stripe_public_key' => $this->getParameter('stripe_public_key'),
                ]);
            }

        } catch (CardException $e) {
            $errors[] = 'Carte refusée : ' . $e->getError()->message;
            return $this->render('client/abonnement_paiement.html.twig', [
                'abonnement'        => $abonnement,
                'dateExpiration'    => $dateExp,
                'errors'            => $errors,
                'cardHolder'        => $cardHolder,
                'stripe_public_key' => $this->getParameter('stripe_public_key'),
            ]);
        } catch (\Throwable $e) {
            // TND non supporté en test — on continue quand même
        }

        $ancienAchat = $achatRepo->findAbonnementActifByUser($userId);
        if ($ancienAchat) {
            $ancienAchat->setStatut('annulé');
        }

        $achat = new Achat();
        $achat->setIdAbonnement($abonnement->getId());
        $achat->setIdUtilisateur($userId);
        $achat->setStatut('actif');
        $achat->setDateAchat(new \DateTime());
        $em->persist($achat);
        $em->flush();

        $dateExpiration = (clone $achat->getDateAchat())->modify('+' . $abonnement->getDureeMois() . ' months');

        $numero   = 'MG-' . str_pad((string)$achat->getId(), 6, '0', STR_PAD_LEFT);
        $qrData   = $qrService->buildAbonnementQrData($numero, $abonnement->getNom(), $dateExpiration->format('d/m/Y'));
        $qrBase64 = $qrService->generateBase64($qrData, 180);

        $user = $userRepo->find($session->get('user_id'));
        $emailEnvoye = null;
        if ($user && $user->getEmail()) {
            try {
                $emailService->sendConfirmationAbonnement(
                    toEmail:        $user->getEmail(),
                    toName:         $user->getNom() . ' ' . $user->getPrenom(),
                    abonnementNom:  $abonnement->getNom(),
                    prix:           (float) $abonnement->getPrix(),
                    dureeMois:      $abonnement->getDureeMois(),
                    dateAchat:      $achat->getDateAchat()->format('d/m/Y à H:i'),
                    dateExpiration: $dateExpiration->format('d/m/Y'),
                    numeroRecu:     $numero,
                    qrCodeBase64:   $qrBase64
                );
                $emailEnvoye = $user->getEmail();
            } catch (\Throwable $e) {
                // Email non bloquant
            }
        }

        return $this->render('client/abonnement_succes.html.twig', [
            'abonnement'     => $abonnement,
            'achat'          => $achat,
            'dateExpiration' => $dateExpiration,
            'numero'         => $numero,
            'qrBase64'       => $qrBase64,
            'emailEnvoye'    => $emailEnvoye,
        ]);
    }

    #[Route('/abonnements/annuler/{id}', name: 'abonnement_annuler')]
    public function annuler(Achat $achat, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');
        if ($achat->getIdUtilisateur() !== $session->get('user_id')) {
            return $this->redirectToRoute('abonnements');
        }
        $achat->setStatut('annulé');
        $em->flush();
        $this->addFlash('success', '❌ Abonnement annulé avec succès.');
        return $this->redirectToRoute('abonnements');
    }

    // ============================================================
    // BACKOFFICE ADMIN
    // ============================================================

    #[Route('/admin/abonnements', name: 'admin_abonnements')]
    public function adminIndex(AbonnementRepository $repo, AchatRepository $achatRepo, SessionInterface $session, CurrencyService $currencyService): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        return $this->render('admin/abonnement.html.twig', [
            'abonnements'      => $repo->findAllOrderedByPrix(),
            'statsParAbo'      => $achatRepo->getStatsParAbonnement(),
            'errors'           => [],
            'currency_options' => $currencyService->getOptionsList(),
        ]);
    }

    #[Route('/admin/abonnements/new', name: 'admin_abonnement_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em, AbonnementRepository $repo, SessionInterface $session, CurrencyService $currencyService): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        $nom         = trim($request->request->get('nom', ''));
        $prix        = $request->request->get('prix', '');
        $duree       = $request->request->get('dureeMois', '');
        $description = trim($request->request->get('description', ''));

        $errors = [];

        if (empty($nom)) {
            $errors['nom'] = 'Le nom est obligatoire';
        } elseif (strlen($nom) < 2 || strlen($nom) > 100) {
            $errors['nom'] = 'Le nom doit faire entre 2 et 100 caractères';
        }

        // ✅ Vérification unicité du nom
        if (empty($errors['nom'])) {
            $existant = $repo->findOneBy(['nom' => $nom]);
            if ($existant) {
                $errors['nom'] = 'Un abonnement avec ce nom existe déjà';
            }
        }

        if ($prix === '' || $prix === null) {
            $errors['prix'] = 'Le prix est obligatoire';
        } elseif (!is_numeric($prix) || (float)$prix <= 0) {
            $errors['prix'] = 'Le prix doit être un nombre positif';
        } elseif ((float)$prix > 9999.99) {
            $errors['prix'] = 'Le prix ne peut pas dépasser 9 999,99 TND';
        }

        if (empty($duree) || !in_array((int)$duree, [1, 3, 6, 12, 24])) {
            $errors['duree'] = 'Durée invalide';
        }

        if (empty($description)) {
            $errors['description'] = 'La description est obligatoire';
        } elseif (strlen($description) < 5) {
            $errors['description'] = 'La description doit avoir au moins 5 caractères';
        } elseif (strlen($description) > 255) {
            $errors['description'] = 'La description ne peut pas dépasser 255 caractères';
        }

        if (!empty($errors)) {
            return $this->render('admin/abonnement.html.twig', [
                'abonnements'      => $repo->findAllOrderedByPrix(),
                'statsParAbo'      => [],
                'errors'           => $errors,
                'form'             => compact('nom', 'prix', 'duree', 'description'),
                'currency_options' => $currencyService->getOptionsList(),
            ]);
        }

        $abo = new Abonnement();
        $abo->setNom($nom);
        $abo->setDescription($description);
        $abo->setPrix((string)(float)$prix);
        $abo->setDureeMois((int)$duree);
        $em->persist($abo);
        $em->flush();

        $this->addFlash('success', '✅ Abonnement "' . $abo->getNom() . '" ajouté avec succès !');

        return $this->redirectToRoute('admin_abonnements');
    }

    #[Route('/admin/abonnements/edit/{id}', name: 'admin_abonnement_edit', methods: ['POST'])]
    public function edit(Request $request, Abonnement $abonnement, EntityManagerInterface $em, AbonnementRepository $repo, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        $nom         = trim($request->request->get('nom', ''));
        $prix        = $request->request->get('prix', '');
        $duree       = $request->request->get('dureeMois', '');
        $description = trim($request->request->get('description', ''));

        $errors = [];

        if (empty($nom) || strlen($nom) < 2) {
            $errors['nom'] = 'Le nom doit faire au moins 2 caractères';
        }

        // ✅ Vérification unicité du nom (sauf abonnement actuel)
        if (empty($errors['nom'])) {
            $existant = $repo->findOneBy(['nom' => $nom]);
            if ($existant && $existant->getId() !== $abonnement->getId()) {
                $errors['nom'] = 'Un abonnement avec ce nom existe déjà';
            }
        }

        if (!is_numeric($prix) || (float)$prix <= 0) {
            $errors['prix'] = 'Le prix doit être un nombre positif';
        }

        if (!in_array((int)$duree, [1, 3, 6, 12, 24])) {
            $errors['duree'] = 'Durée invalide';
        }

        if (empty($description)) {
            $errors['description'] = 'La description est obligatoire';
        } elseif (strlen($description) < 5) {
            $errors['description'] = 'La description doit avoir au moins 5 caractères';
        } elseif (strlen($description) > 255) {
            $errors['description'] = 'La description ne peut pas dépasser 255 caractères';
        }

        if (!empty($errors)) {
            return $this->redirectToRoute('admin_abonnements');
        }

        $abonnement->setNom($nom);
        $abonnement->setDescription($description);
        $abonnement->setPrix((string)(float)$prix);
        $abonnement->setDureeMois((int)$duree);
        $em->flush();

        $this->addFlash('success', '✏️ Abonnement "' . $abonnement->getNom() . '" modifié avec succès !');

        return $this->redirectToRoute('admin_abonnements');
    }

    #[Route('/admin/abonnements/delete/{id}', name: 'admin_abonnement_delete')]
    public function delete(Abonnement $abonnement, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        $nom = $abonnement->getNom();
        $em->remove($abonnement);
        $em->flush();
        $this->addFlash('success', '🗑️ Abonnement "' . $nom . '" supprimé avec succès !');
        return $this->redirectToRoute('admin_abonnements');
    }

    // ──────────────────────────────────────────────
    // REÇU PDF ABONNEMENT
    // ──────────────────────────────────────────────
    #[Route('/abonnements/recu/{id}', name: 'abonnement_recu')]
    public function recu(
        int $id,
        AchatRepository $achatRepo,
        AbonnementRepository $abonnementRepo,
        PdfService $pdfService,
        SessionInterface $session
    ): Response {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');

        $achat = $achatRepo->find($id);
        if (!$achat || $achat->getIdUtilisateur() !== $session->get('user_id')) {
            throw $this->createNotFoundException('Reçu introuvable');
        }

        // Récupérer l'abonnement via son ID
        $abonnement = $abonnementRepo->find($achat->getIdAbonnement());

        $pdfContent = $pdfService->generateRecuAbonnement([
            'numero'         => 'AB-' . str_pad((string)$achat->getId(), 5, '0', STR_PAD_LEFT),
            'date'           => $achat->getDateAchat() ? $achat->getDateAchat()->format('d/m/Y') : date('d/m/Y'),
            'client'         => ($session->get('user_nom') ?? '') . ' ' . ($session->get('user_prenom') ?? ''),
            'abonnement'     => $abonnement ? $abonnement->getNom() : 'N/A',
            'duree'          => $abonnement ? $abonnement->getDureeMois() : 'N/A',
            'dateDebut'      => $achat->getDateAchat() ? $achat->getDateAchat()->format('d/m/Y') : 'N/A',
            'dateExpiration' => $abonnement && $achat->getDateAchat()
                                    ? $achat->getDateAchat()->modify('+' . $abonnement->getDureeMois() . ' months')->format('d/m/Y')
                                    : 'N/A',
            'prix'           => $abonnement ? $abonnement->getPrix() : '0',
        ]);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition',
            'inline; filename="recu_abonnement_' . $achat->getId() . '.pdf"'
        );
        return $response;
    }

    // ──────────────────────────────────────────────
    // PRÉVISUALISATION EMAIL (pour démonstration)
    // ──────────────────────────────────────────────
    #[Route('/abonnements/email-preview/{id}', name: 'abonnement_email_preview')]
    public function emailPreview(
        int $id,
        AchatRepository $achatRepo,
        AbonnementRepository $abonnementRepo,
        QrCodeService $qrService,
        SessionInterface $session
    ): Response {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');

        $achat = $achatRepo->find($id);
        if (!$achat || $achat->getIdUtilisateur() !== $session->get('user_id')) {
            throw $this->createNotFoundException();
        }

        $abonnement    = $abonnementRepo->find($achat->getIdAbonnement());
        $dateExp       = (clone $achat->getDateAchat())->modify('+' . $abonnement->getDureeMois() . ' months');
        $numero        = 'MG-' . str_pad((string)$achat->getId(), 6, '0', STR_PAD_LEFT);
        $qrData        = $qrService->buildAbonnementQrData($numero, $abonnement->getNom(), $dateExp->format('d/m/Y'));
        $qrBase64      = $qrService->generateBase64($qrData, 180);

        $emailService  = new \App\Service\EmailService(
            new class implements \Symfony\Component\Mailer\MailerInterface {
                public function send(\Symfony\Component\Mime\RawMessage $message, ?\Symfony\Component\Mailer\Envelope $envelope = null): void {}
            },
            $this->getParameter('kernel.project_dir')
        );

        // Accès direct au HTML via réflexion
        $ref    = new \ReflectionClass($emailService);
        $method = $ref->getMethod('buildEmailHtml');
        $method->setAccessible(true);
        $html   = $method->invoke($emailService,
            $session->get('user_nom') . ' ' . $session->get('user_prenom'),
            $abonnement->getNom(),
            (float) $abonnement->getPrix(),
            $abonnement->getDureeMois(),
            $achat->getDateAchat()->format('d/m/Y à H:i'),
            $dateExp->format('d/m/Y'),
            $numero,
            $qrBase64
        );

        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }

    // ── TEST EMAIL (debug uniquement) ──
    #[Route('/abonnements/test-email', name: 'abonnement_test_email')]
    public function testEmail(
        EmailService $emailService,
        QrCodeService $qrService,
        SessionInterface $session
    ): Response {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');

        $numero   = 'TEST-' . date('His');
        $qrData   = $qrService->buildAbonnementQrData($numero, 'Test Plan', date('d/m/Y', strtotime('+1 year')));
        $qrBase64 = $qrService->generateBase64($qrData, 180);

        $sent = $emailService->sendConfirmationAbonnement(
            toEmail:        $session->get('user_email') ?? 'adminmindrow@gmail.com',
            toName:         ($session->get('user_nom') ?? 'Test') . ' ' . ($session->get('user_prenom') ?? 'User'),
            abonnementNom:  'Pack Test',
            prix:           50.0,
            dureeMois:      12,
            dateAchat:      date('d/m/Y à H:i'),
            dateExpiration: date('d/m/Y', strtotime('+1 year')),
            numeroRecu:     $numero,
            qrCodeBase64:   $qrBase64
        );

        // Check error log
        $errorFile = $this->getParameter('kernel.project_dir') . '/var/emails/last_error.txt';
        $errorMsg  = file_exists($errorFile) ? file_get_contents($errorFile) : 'Aucune erreur';

        $html = '<html><body style="font-family:sans-serif;padding:40px;">';
        if ($sent) {
            $html .= '<div style="color:green;font-size:18px;font-weight:bold;">✅ Email envoyé avec succès à ' . htmlspecialchars($session->get('user_email') ?? 'adminmindrow@gmail.com') . '</div>';
        } else {
            $html .= '<div style="color:red;font-size:18px;font-weight:bold;">❌ Erreur envoi email</div>';
            $html .= '<pre style="background:#fee;padding:20px;border-radius:8px;margin-top:20px;">' . htmlspecialchars($errorMsg) . '</pre>';
        }
        $html .= '<br><a href="' . $this->generateUrl('abonnements') . '">← Retour</a>';
        $html .= '</body></html>';

        return new Response($html);
    }
}