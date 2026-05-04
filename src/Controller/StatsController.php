<?php
namespace App\Controller;

use App\Repository\AchatRepository;
use App\Repository\AbonnementRepository;
use App\Repository\AvisRepository;
use App\Repository\TherapeuteRepository;
use App\Repository\ProgrammeRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API Statistiques — Dashboard Admin
 * Fournit les données JSON pour Chart.js
 */
class StatsController extends AbstractController
{
    // ── Revenus par mois (12 derniers mois) ──
    #[Route('/api/stats/revenus-mensuels', name: 'stats_revenus_mensuels')]
    public function revenusMensuels(
        EntityManagerInterface $em,
        SessionInterface $session
    ): JsonResponse {
        if ($session->get('user_role') !== 'admin') return $this->json([], 403);

        $conn = $em->getConnection();
        $rows = $conn->fetchAllAssociative("
            SELECT
                DATE_FORMAT(a.date_achat, '%Y-%m') AS mois,
                COUNT(a.id_achat)                  AS nb_achats,
                COALESCE(SUM(ab.prix), 0)          AS revenu
            FROM achat a
            JOIN abonnement ab ON ab.id_abonnement = a.id_abonnement
            WHERE a.date_achat >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              AND a.statut IN ('actif', 'annulé')
            GROUP BY DATE_FORMAT(a.date_achat, '%Y-%m')
            ORDER BY mois ASC
        ");

        $labels  = [];
        $revenus = [];
        $achats  = [];
        foreach ($rows as $row) {
            $dt       = new \DateTime($row['mois'] . '-01');
            $labels[] = $dt->format('M Y');
            $revenus[] = (float) $row['revenu'];
            $achats[]  = (int)   $row['nb_achats'];
        }

        return $this->json(compact('labels', 'revenus', 'achats'));
    }

    // ── Répartition par plan ──
    #[Route('/api/stats/repartition-plans', name: 'stats_repartition_plans')]
    public function repartitionPlans(
        AchatRepository $achatRepo,
        SessionInterface $session
    ): JsonResponse {
        if ($session->get('user_role') !== 'admin') return $this->json([], 403);

        $stats  = $achatRepo->getStatsParAbonnement();
        $labels = array_column($stats, 'nom');
        $actifs = array_map('intval', array_column($stats, 'actifs'));
        $rev    = array_map('floatval', array_column($stats, 'revenu'));

        return $this->json(['labels' => $labels, 'actifs' => $actifs, 'revenus' => $rev]);
    }

    // ── Page dashboard statistiques ──
    #[Route('/admin/statistiques', name: 'admin_stats_abonnements')]
    public function dashboard(SessionInterface $session): \Symfony\Component\HttpFoundation\Response
    {
        if ($session->get('user_role') !== 'admin') {
            return $this->redirectToRoute('login');
        }
        return $this->render('admin/statistiques.html.twig');
    }

    // ── Statuts des achats ──
    #[Route('/api/stats/statuts', name: 'stats_statuts')]
    public function statuts(
        EntityManagerInterface $em,
        SessionInterface $session
    ): JsonResponse {
        if ($session->get('user_role') !== 'admin') return $this->json([], 403);

        $conn = $em->getConnection();
        $rows = $conn->fetchAllAssociative("
            SELECT statut, COUNT(*) AS nb FROM achat GROUP BY statut
        ");

        $labels = array_column($rows, 'statut');
        $values = array_map('intval', array_column($rows, 'nb'));

        return $this->json(['labels' => $labels, 'values' => $values]);
    }

    // ════════════════════════════════════════
    // STATS THÉRAPEUTES / AVIS
    // ════════════════════════════════════════

    // ── Page stats avis (accessible aux clients connectés) ──
    #[Route('/stats/therapeutes', name: 'stats_therapeutes')]
    public function statsThérapeutes(SessionInterface $session): Response
    {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');
        return $this->render('client/stats_therapeutes.html.twig');
    }

    // ── JSON : distribution globale des notes ──
    #[Route('/api/stats/avis/distribution', name: 'api_stats_avis_distribution')]
    public function avisDistribution(AvisRepository $avisRepo, SessionInterface $session): JsonResponse
    {
        if (!$session->get('user_id')) return $this->json([], 401);
        $dist   = $avisRepo->getGlobalDistribution();
        $labels = ['1 ★', '2 ★', '3 ★', '4 ★', '5 ★'];
        $values = array_values($dist);
        return $this->json(compact('labels', 'values'));
    }

    // ── JSON : top thérapeutes par note moyenne ──
    #[Route('/api/stats/avis/top-moyenne', name: 'api_stats_avis_top_moyenne')]
    public function avisTopMoyenne(AvisRepository $avisRepo, TherapeuteRepository $thRepo, SessionInterface $session): JsonResponse
    {
        if (!$session->get('user_id')) return $this->json([], 401);
        $rows   = $avisRepo->getTopByMoyenne(8);
        $labels = [];
        $values = [];
        $counts = [];
        foreach ($rows as $r) {
            $th       = $thRepo->find($r['idTherapeute']);
            $labels[] = $th ? $th->getPrenom() . ' ' . $th->getNom() : '#' . $r['idTherapeute'];
            $values[] = round((float)$r['moyenne'], 1);
            $counts[] = (int)$r['nbAvis'];
        }
        return $this->json(compact('labels', 'values', 'counts'));
    }

    // ── JSON : top thérapeutes par nombre d'avis ──
    #[Route('/api/stats/avis/top-nb', name: 'api_stats_avis_top_nb')]
    public function avisTopNb(AvisRepository $avisRepo, TherapeuteRepository $thRepo, SessionInterface $session): JsonResponse
    {
        if (!$session->get('user_id')) return $this->json([], 401);
        $rows   = $avisRepo->getTopByNbAvis(8);
        $labels = [];
        $values = [];
        $moyennes = [];
        foreach ($rows as $r) {
            $th       = $thRepo->find($r['idTherapeute']);
            $labels[] = $th ? $th->getPrenom() . ' ' . $th->getNom() : '#' . $r['idTherapeute'];
            $values[] = (int)$r['nbAvis'];
            $moyennes[] = round((float)$r['moyenne'], 1);
        }
        return $this->json(compact('labels', 'values', 'moyennes'));
    }

    // ── JSON : avis par mois (12 derniers mois) ──
    #[Route('/api/stats/avis/par-mois', name: 'api_stats_avis_par_mois')]
    public function avisParMois(AvisRepository $avisRepo, SessionInterface $session): JsonResponse
    {
        if (!$session->get('user_id')) return $this->json([], 401);
        $rows   = $avisRepo->getReviewsPerMonth();
        $labels = [];
        $values = [];
        foreach ($rows as $r) {
            $dt       = \DateTime::createFromFormat('Y-m', $r['mois']);
            $labels[] = $dt ? $dt->format('M Y') : $r['mois'];
            $values[] = (int)$r['nb'];
        }
        return $this->json(compact('labels', 'values'));
    }

    // ════════════════════════════════════════
    // STATS PROGRAMMES
    // ════════════════════════════════════════

    // ── Page statistiques programmes ──
    #[Route('/admin/statistiques/programmes', name: 'admin_stats_programmes')]
    public function statsProgrammes(SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') {
            return $this->redirectToRoute('login');
        }
        return $this->render('admin/statistiques_programmes.html.twig');
    }

    // ── JSON : KPIs globaux programmes ──
    #[Route('/api/stats/programmes/kpis', name: 'api_stats_programmes_kpis')]
    public function programmesKpis(EntityManagerInterface $em, SessionInterface $session): JsonResponse
    {
        if ($session->get('user_role') !== 'admin') return $this->json([], 403);
        $conn = $em->getConnection();
        $r = $conn->fetchAssociative("
            SELECT
                COUNT(*) AS total_programmes,
                COUNT(DISTINCT id_categorie) AS total_categories,
                SUM(CASE WHEN image IS NOT NULL AND image != '' THEN 1 ELSE 0 END) AS avec_image,
                SUM(CASE WHEN video IS NOT NULL AND video != '' THEN 1 ELSE 0 END) AS avec_video
            FROM programme
        ");
        return $this->json([
            'total_programmes' => (int)$r['total_programmes'],
            'total_categories' => (int)$r['total_categories'],
            'avec_image'       => (int)$r['avec_image'],
            'avec_video'       => (int)$r['avec_video'],
        ]);
    }

    // ── JSON : programmes par catégorie ──
    #[Route('/api/stats/programmes/par-categorie', name: 'api_stats_programmes_par_categorie')]
    public function programmesParCategorie(EntityManagerInterface $em, SessionInterface $session): JsonResponse
    {
        if ($session->get('user_role') !== 'admin') return $this->json([], 403);
        $conn = $em->getConnection();
        $rows = $conn->fetchAllAssociative("
            SELECT c.nom AS categorie, COUNT(p.id_programme) AS nb
            FROM categorie c
            LEFT JOIN programme p ON p.id_categorie = c.id_categorie
            GROUP BY c.id_categorie, c.nom
            ORDER BY nb DESC
        ");
        return $this->json([
            'labels' => array_column($rows, 'categorie'),
            'values' => array_map('intval', array_column($rows, 'nb')),
        ]);
    }

    // ── JSON : couverture médias programmes ──
    #[Route('/api/stats/programmes/medias', name: 'api_stats_programmes_medias')]
    public function programmesMedias(EntityManagerInterface $em, SessionInterface $session): JsonResponse
    {
        if ($session->get('user_role') !== 'admin') return $this->json([], 403);
        $conn = $em->getConnection();
        $r = $conn->fetchAssociative("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN image IS NOT NULL AND image != '' THEN 1 ELSE 0 END) AS avec_image,
                SUM(CASE WHEN video IS NOT NULL AND video != '' THEN 1 ELSE 0 END) AS avec_video
            FROM programme
        ");
        $total = (int)$r['total'];
        return $this->json([
            'total'      => $total,
            'avec_image' => (int)$r['avec_image'],
            'sans_image' => $total - (int)$r['avec_image'],
            'avec_video' => (int)$r['avec_video'],
            'sans_video' => $total - (int)$r['avec_video'],
        ]);
    }
}
