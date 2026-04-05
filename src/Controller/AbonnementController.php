<?php
namespace App\Controller;

use App\Entity\Abonnement;
use App\Entity\Achat;
use App\Repository\AbonnementRepository;
use App\Repository\AchatRepository;
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
        Request $request
    ): Response {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');

        // ✅ Lecture des 3 paramètres de filtre
        $q     = trim($request->query->get('q', ''));
        $duree = (int)$request->query->get('duree', 0);
        $tri   = $request->query->get('tri', 'populaire');

        // ✅ findByFilters si un filtre est actif, sinon tri populaire par défaut
        $filtreActif = ($q !== '' || $duree > 0 || $tri !== 'populaire');
        $abonnements = $filtreActif
            ? $repo->findByFilters($q, $duree, $tri)
            : $repo->findAllOrderedByPrix();

        $userId     = $session->get('user_id');
        $achatActif = $achatRepo->findAbonnementActifByUser($userId);

        // ✅ CORRIGÉ : retourne 0/0/0 si aucun actif → stats masquées dans la vue
        $stats = $achatRepo->getStatsAbonnements();

        return $this->render('abonnement/index.html.twig', [
            'abonnements' => $abonnements,
            'achatActif'  => $achatActif,
            'stats'       => $stats,
        ]);
    }

    #[Route('/abonnements/paiement/{id}', name: 'abonnement_paiement')]
    public function paiement(Abonnement $abonnement, AchatRepository $achatRepo, SessionInterface $session): Response
    {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');

        $dateExpiration = (new \DateTime())->modify('+' . $abonnement->getDureeMois() . ' months');

        return $this->render('abonnement/paiement.html.twig', [
            'abonnement'     => $abonnement,
            'dateExpiration' => $dateExpiration,
            'errors'         => [],
        ]);
    }

    #[Route('/abonnements/payer/{id}', name: 'abonnement_payer', methods: ['POST'])]
    public function payer(
        Request $request,
        Abonnement $abonnement,
        EntityManagerInterface $em,
        AchatRepository $achatRepo,
        SessionInterface $session
    ): Response {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');

        $cardNumber = preg_replace('/\s+/', '', $request->request->get('cardNumber', ''));
        $expiry     = trim($request->request->get('expiry', ''));
        $cvc        = trim($request->request->get('cvc', ''));
        $cardHolder = trim($request->request->get('cardHolder', ''));

        $errors = [];

        if (empty($cardHolder)) {
            $errors[] = 'Le nom du titulaire est obligatoire';
        } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]{2,50}$/', $cardHolder)) {
            $errors[] = 'Le nom du titulaire ne doit contenir que des lettres (2-50 caractères)';
        }

        if (!preg_match('/^\d{16}$/', $cardNumber)) {
            $errors[] = 'Numéro de carte invalide — 16 chiffres requis';
        }

        if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
            $errors[] = 'Date d\'expiration invalide — format MM/AA requis';
        } else {
            $expDate = \DateTime::createFromFormat('m/y', $expiry);
            $expDate->modify('last day of this month');
            if ($expDate < new \DateTime()) {
                $errors[] = 'Cette carte est expirée';
            }
        }

        if (!preg_match('/^\d{3,4}$/', $cvc)) {
            $errors[] = 'CVC invalide — 3 ou 4 chiffres requis';
        }

        if (!empty($errors)) {
            return $this->render('abonnement/paiement.html.twig', [
                'abonnement'     => $abonnement,
                'dateExpiration' => (new \DateTime())->modify('+' . $abonnement->getDureeMois() . ' months'),
                'errors'         => $errors,
                'cardHolder'     => $cardHolder,
            ]);
        }

        // 🔐 Appel Stripe
        try {
            \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
            \Stripe\PaymentIntent::create([
                'amount'   => (int)($abonnement->getPrix() * 100),
                'currency' => 'usd',
                'metadata' => [
                    'user_id'       => $session->get('user_id'),
                    'abonnement_id' => $abonnement->getId(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->render('abonnement/paiement.html.twig', [
                'abonnement'     => $abonnement,
                'dateExpiration' => (new \DateTime())->modify('+' . $abonnement->getDureeMois() . ' months'),
                'errors'         => ['Erreur de paiement : ' . $e->getMessage()],
                'cardHolder'     => $cardHolder,
            ]);
        }

        $userId = $session->get('user_id');

        // Annuler l'ancien abonnement actif
        $ancienAchat = $achatRepo->findAbonnementActifByUser($userId);
        if ($ancienAchat) {
            $ancienAchat->setStatut('annulé');
        }

        // Créer le nouvel achat
        $achat = new Achat();
        $achat->setIdAbonnement($abonnement->getId());
        $achat->setIdUtilisateur($userId);
        $achat->setStatut('actif');
        $achat->setDateAchat(new \DateTime());
        $em->persist($achat);
        $em->flush();

        $dateExpiration = (clone $achat->getDateAchat())->modify('+' . $abonnement->getDureeMois() . ' months');

        return $this->render('abonnement/succes.html.twig', [
            'abonnement'     => $abonnement,
            'achat'          => $achat,
            'dateExpiration' => $dateExpiration,
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
        return $this->redirectToRoute('abonnements');
    }

    // ============================================================
    // BACKOFFICE ADMIN
    // ============================================================

    #[Route('/admin/abonnements', name: 'admin_abonnements')]
    public function adminIndex(AbonnementRepository $repo, AchatRepository $achatRepo, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        return $this->render('abonnement/admin.html.twig', [
            'abonnements' => $repo->findAllOrderedByPrix(),
            'statsParAbo' => $achatRepo->getStatsParAbonnement(),
            'errors'      => [],
        ]);
    }

    #[Route('/admin/abonnements/new', name: 'admin_abonnement_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em, AbonnementRepository $repo, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        $nom         = trim($request->request->get('nom', ''));
        $prix        = $request->request->get('prix', '');
        $duree       = $request->request->get('dureeMois', '');
        $description = trim($request->request->get('description', ''));

        $errors = [];

        if (empty($nom)) {
            $errors[] = 'Le nom est obligatoire';
        } elseif (strlen($nom) < 2 || strlen($nom) > 100) {
            $errors[] = 'Le nom doit faire entre 2 et 100 caractères';
        }

        if ($prix === '' || $prix === null) {
            $errors[] = 'Le prix est obligatoire';
        } elseif (!is_numeric($prix) || (float)$prix <= 0) {
            $errors[] = 'Le prix doit être un nombre positif';
        } elseif ((float)$prix > 9999.99) {
            $errors[] = 'Le prix ne peut pas dépasser 9 999,99 TND';
        }

        if (empty($duree) || !in_array((int)$duree, [1, 3, 6, 12, 24])) {
            $errors[] = 'Durée invalide — choisissez parmi 1, 3, 6, 12 ou 24 mois';
        }

        if (!empty($errors)) {
            return $this->render('abonnement/admin.html.twig', [
                'abonnements' => $repo->findAllOrderedByPrix(),
                'statsParAbo' => [],
                'errors'      => $errors,
                'form'        => compact('nom', 'prix', 'duree', 'description'),
            ]);
        }

        $abo = new Abonnement();
        $abo->setNom($nom);
        $abo->setDescription($description ?: null);
        $abo->setPrix((string)(float)$prix);
        $abo->setDureeMois((int)$duree);
        $em->persist($abo);
        $em->flush();

        return $this->redirectToRoute('admin_abonnements');
    }

    #[Route('/admin/abonnements/edit/{id}', name: 'admin_abonnement_edit', methods: ['POST'])]
    public function edit(Request $request, Abonnement $abonnement, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        $nom   = trim($request->request->get('nom', ''));
        $prix  = $request->request->get('prix', '');
        $duree = $request->request->get('dureeMois', '');

        $errors = [];
        if (empty($nom) || strlen($nom) < 2) $errors[] = 'Le nom doit faire au moins 2 caractères';
        if (!is_numeric($prix) || (float)$prix <= 0) $errors[] = 'Le prix doit être un nombre positif';
        if (!in_array((int)$duree, [1, 3, 6, 12, 24])) $errors[] = 'Durée invalide';

        if (!empty($errors)) {
            return $this->redirectToRoute('admin_abonnements');
        }

        $abonnement->setNom($nom);
        $abonnement->setDescription(trim($request->request->get('description', '')) ?: null);
        $abonnement->setPrix((string)(float)$prix);
        $abonnement->setDureeMois((int)$duree);
        $em->flush();

        return $this->redirectToRoute('admin_abonnements');
    }

    #[Route('/admin/abonnements/delete/{id}', name: 'admin_abonnement_delete')]
    public function delete(Abonnement $abonnement, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        $em->remove($abonnement);
        $em->flush();
        return $this->redirectToRoute('admin_abonnements');
    }
}