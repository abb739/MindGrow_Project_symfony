<?php
namespace App\Controller;

use App\Entity\Programme;
use App\Entity\Categorie;
use App\Entity\FavoriProgramme;
use App\Repository\ProgrammeRepository;
use App\Repository\CategorieRepository;
use App\Repository\FavoriProgrammeRepository;
use App\Repository\SeanceRepository;
use App\Repository\ReservationRepository;
use App\Service\GeminiService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * ╔══════════════════════════════════════════════════════════╗
 * ║         API REST — Module Programme (MindGrow)          ║
 * ║  Toutes les routes commencent par /api/programmes       ║
 * ╚══════════════════════════════════════════════════════════╝
 *
 * Authentification : session PHP (user_id / user_role)
 * Format de réponse : JSON
 */
#[Route('/api/programmes', name: 'api_programme_')]
class ApiProgrammeController extends AbstractController
{
    // =========================================================================
    // 1. LISTE TOUS LES PROGRAMMES  GET /api/programmes
    // =========================================================================
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        Request $request,
        ProgrammeRepository $repo,
        CategorieRepository $catRepo,
        FavoriProgrammeRepository $favRepo,
        SessionInterface $session
    ): JsonResponse {
        if (!$session->get('user_id')) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $search = $request->query->get('search', '');
        $catId  = $request->query->get('categorie', '');
        $sort   = $request->query->get('sort', 'id');   // id | titre | favoris
        $limit  = min((int)$request->query->get('limit', 50), 100);
        $offset = (int)$request->query->get('offset', 0);

        // Récupération avec filtre
        $programmes = ($search || $catId)
            ? $repo->findByFilters($search, $catId)
            : $repo->findAll();

        // Tri côté PHP si demandé par favoris (nécessite le count)
        $userId = $session->get('user_id');
        $categories = [];
        foreach ($catRepo->findAll() as $c) {
            $categories[$c->getId()] = $c->getNom();
        }

        $data = [];
        foreach ($programmes as $p) {
            $data[] = $this->serializeProgramme($p, $categories, $favRepo, $userId);
        }

        // Tri
        if ($sort === 'titre') {
            usort($data, fn($a, $b) => strcmp($a['titre'], $b['titre']));
        } elseif ($sort === 'favoris') {
            usort($data, fn($a, $b) => $b['nb_favoris'] - $a['nb_favoris']);
        }

        $total = count($data);
        $data  = array_slice($data, $offset, $limit);

        return $this->json([
            'total'      => $total,
            'limit'      => $limit,
            'offset'     => $offset,
            'programmes' => $data,
        ]);
    }

    // =========================================================================
    // 2. DETAIL D'UN PROGRAMME   GET /api/programmes/{id}
    // =========================================================================
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        Programme $programme,
        CategorieRepository $catRepo,
        FavoriProgrammeRepository $favRepo,
        SeanceRepository $seanceRepo,
        SessionInterface $session
    ): JsonResponse {
        if (!$session->get('user_id')) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $categories = [];
        foreach ($catRepo->findAll() as $c) {
            $categories[$c->getId()] = $c->getNom();
        }

        $userId = $session->get('user_id');
        $data   = $this->serializeProgramme($programme, $categories, $favRepo, $userId);

        // Séances à venir liées à ce programme (match sur le titre)
        $seances = $seanceRepo->findSeancesAVenir();
        $seancesLiees = array_filter($seances, fn($s) =>
            stripos($s->getTitre(), $programme->getTitre()) !== false
        );

        $data['seances'] = array_values(array_map(fn($s) => [
            'id'         => $s->getId(),
            'titre'      => $s->getTitre(),
            'lieu'       => $s->getLieu(),
            'date_debut' => $s->getDateDebut()?->format('Y-m-d H:i'),
            'date_fin'   => $s->getDateFin()?->format('Y-m-d H:i'),
            'capacite'   => $s->getCapacite(),
        ], $seancesLiees));

        return $this->json($data);
    }

    // =========================================================================
    // 3. CRÉER UN PROGRAMME   POST /api/programmes
    // =========================================================================
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $session
    ): JsonResponse {
        if ($session->get('user_role') !== 'admin') {
            return $this->json(['error' => 'Accès refusé (admin uniquement)'], 403);
        }

        $data = json_decode($request->getContent(), true);

        // Validation
        if (empty($data['titre'])) {
            return $this->json(['error' => 'Le titre est obligatoire'], 422);
        }
        if (empty($data['id_categorie'])) {
            return $this->json(['error' => 'La catégorie est obligatoire'], 422);
        }
        if (!preg_match('/^[a-zA-Z0-9\s\-\'À-ÿ]+$/u', $data['titre'])) {
            return $this->json(['error' => 'Titre invalide (caractères non autorisés)'], 422);
        }

        $programme = new Programme();
        $programme->setTitre(trim($data['titre']));
        $programme->setDescription($data['description'] ?? null);
        $programme->setIdCategorie((int)$data['id_categorie']);

        $em->persist($programme);
        $em->flush();

        return $this->json([
            'message' => 'Programme créé avec succès',
            'id'      => $programme->getId(),
        ], 201);
    }

    // =========================================================================
    // 4. MODIFIER UN PROGRAMME   PUT /api/programmes/{id}
    // =========================================================================
    #[Route('/{id}', name: 'update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(
        Programme $programme,
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $session
    ): JsonResponse {
        if ($session->get('user_role') !== 'admin') {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!empty($data['titre'])) {
            if (!preg_match('/^[a-zA-Z0-9\s\-\'À-ÿ]+$/u', $data['titre'])) {
                return $this->json(['error' => 'Titre invalide'], 422);
            }
            $programme->setTitre(trim($data['titre']));
        }

        if (array_key_exists('description', $data)) {
            $programme->setDescription($data['description']);
        }

        if (!empty($data['id_categorie'])) {
            $programme->setIdCategorie((int)$data['id_categorie']);
        }

        $em->flush();

        return $this->json(['message' => 'Programme modifié avec succès']);
    }

    // =========================================================================
    // 5. SUPPRIMER UN PROGRAMME   DELETE /api/programmes/{id}
    // =========================================================================
    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(
        Programme $programme,
        EntityManagerInterface $em,
        SessionInterface $session
    ): JsonResponse {
        if ($session->get('user_role') !== 'admin') {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $publicDir = $this->getParameter('kernel.project_dir') . '/public/';
        if ($programme->getImage()) @unlink($publicDir . $programme->getImage());
        if ($programme->getVideo()) @unlink($publicDir . $programme->getVideo());

        $em->remove($programme);
        $em->flush();

        return $this->json(['message' => 'Programme supprimé']);
    }

    // =========================================================================
    // 6. PROGRAMMES PAR CATÉGORIE   GET /api/programmes/categorie/{id}
    // =========================================================================
    #[Route('/categorie/{id}', name: 'by_categorie', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function byCategorie(
        int $id,
        ProgrammeRepository $repo,
        CategorieRepository $catRepo,
        FavoriProgrammeRepository $favRepo,
        SessionInterface $session
    ): JsonResponse {
        if (!$session->get('user_id')) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $programmes = $repo->findByCategorie($id);

        $categories = [];
        foreach ($catRepo->findAll() as $c) {
            $categories[$c->getId()] = $c->getNom();
        }

        $userId = $session->get('user_id');
        $data   = array_map(fn($p) => $this->serializeProgramme($p, $categories, $favRepo, $userId), $programmes);

        return $this->json([
            'categorie_id' => $id,
            'total'        => count($data),
            'programmes'   => $data,
        ]);
    }

    // =========================================================================
    // 7. RECHERCHE FULL-TEXT   GET /api/programmes/search?q=yoga
    // =========================================================================
    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(
        Request $request,
        ProgrammeRepository $repo,
        CategorieRepository $catRepo,
        FavoriProgrammeRepository $favRepo,
        SessionInterface $session
    ): JsonResponse {
        if (!$session->get('user_id')) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 2) {
            return $this->json(['error' => 'La recherche doit contenir au moins 2 caractères'], 422);
        }

        $programmes = $repo->searchFullText($q);

        $categories = [];
        foreach ($catRepo->findAll() as $c) {
            $categories[$c->getId()] = $c->getNom();
        }

        $userId = $session->get('user_id');
        $data   = array_map(fn($p) => $this->serializeProgramme($p, $categories, $favRepo, $userId), $programmes);

        return $this->json([
            'query'      => $q,
            'total'      => count($data),
            'programmes' => $data,
        ]);
    }

    // =========================================================================
    // 8. STATISTIQUES GLOBALES   GET /api/programmes/stats
    // =========================================================================
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(
        ProgrammeRepository $repo,
        CategorieRepository $catRepo,
        FavoriProgrammeRepository $favRepo,
        SeanceRepository $seanceRepo,
        Connection $conn,
        SessionInterface $session
    ): JsonResponse {
        if ($session->get('user_role') !== 'admin') {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        // Nb total programmes
        $totalProgrammes = $repo->countTotal();

        // Répartition par catégorie
        $byCategorie    = $repo->countByCategorie();
        $catNames       = [];
        foreach ($catRepo->findAll() as $c) {
            $catNames[$c->getId()] = $c->getNom();
        }
        $repartition = array_map(fn($r) => [
            'categorie' => $catNames[$r['categorie_id']] ?? 'Inconnu',
            'total'     => (int)$r['total'],
        ], $byCategorie);

        // Programme le + favori
        $allProgrammes   = $repo->findAll();
        $topFavoriId     = null;
        $topFavoriNb     = 0;
        $topFavoriTitre  = '';
        foreach ($allProgrammes as $p) {
            $nb = $favRepo->countByProgramme($p->getId());
            if ($nb > $topFavoriNb) {
                $topFavoriNb    = $nb;
                $topFavoriId    = $p->getId();
                $topFavoriTitre = $p->getTitre();
            }
        }

        // Programmes récents
        $recents = array_map(fn($p) => [
            'id'    => $p->getId(),
            'titre' => $p->getTitre(),
        ], $repo->findRecents(5));

        // Nb séances totales
        $totalSeances = count($seanceRepo->findAll());

        return $this->json([
            'total_programmes' => $totalProgrammes,
            'total_seances'    => $totalSeances,
            'repartition_par_categorie' => $repartition,
            'programme_plus_favori' => [
                'id'        => $topFavoriId,
                'titre'     => $topFavoriTitre,
                'nb_favoris'=> $topFavoriNb,
            ],
            'programmes_recents' => $recents,
        ]);
    }

    // =========================================================================
    // 9. TOGGLE FAVORI   POST /api/programmes/{id}/favori
    // =========================================================================
    #[Route('/{id}/favori', name: 'favori_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleFavori(
        Programme $programme,
        EntityManagerInterface $em,
        FavoriProgrammeRepository $favRepo,
        SessionInterface $session
    ): JsonResponse {
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $existing = $favRepo->findOneBy([
            'idUtilisateur' => $userId,
            'idProgramme'   => $programme->getId(),
        ]);

        if ($existing) {
            // Retirer des favoris
            $em->remove($existing);
            $em->flush();
            return $this->json([
                'action'     => 'removed',
                'message'    => 'Programme retiré des favoris',
                'nb_favoris' => $favRepo->countByProgramme($programme->getId()),
            ]);
        } else {
            // Ajouter aux favoris
            $favori = new FavoriProgramme();
            $favori->setIdUtilisateur($userId);
            $favori->setIdProgramme($programme->getId());
            $em->persist($favori);
            $em->flush();
            return $this->json([
                'action'     => 'added',
                'message'    => 'Programme ajouté aux favoris',
                'nb_favoris' => $favRepo->countByProgramme($programme->getId()),
            ], 201);
        }
    }

    // =========================================================================
    // 10. MES FAVORIS   GET /api/programmes/mes-favoris
    // =========================================================================
    #[Route('/mes-favoris', name: 'mes_favoris', methods: ['GET'])]
    public function mesFavoris(
        FavoriProgrammeRepository $favRepo,
        ProgrammeRepository $repo,
        CategorieRepository $catRepo,
        SessionInterface $session
    ): JsonResponse {
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $favoris    = $favRepo->findByUtilisateur($userId);
        $categories = [];
        foreach ($catRepo->findAll() as $c) {
            $categories[$c->getId()] = $c->getNom();
        }

        $data = [];
        foreach ($favoris as $f) {
            $prog = $repo->find($f->getIdProgramme());
            if ($prog) {
                $d                = $this->serializeProgramme($prog, $categories, $favRepo, $userId);
                $d['favori_date'] = $f->getCreatedAt()?->format('Y-m-d H:i');
                $data[]           = $d;
            }
        }

        return $this->json([
            'total'   => count($data),
            'favoris' => $data,
        ]);
    }

    // =========================================================================
    // 11. SÉANCES D'UN PROGRAMME   GET /api/programmes/{id}/seances
    // =========================================================================
    #[Route('/{id}/seances', name: 'seances', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function seances(
        Programme $programme,
        SeanceRepository $seanceRepo,
        SessionInterface $session
    ): JsonResponse {
        if (!$session->get('user_id')) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // Toutes les séances à venir (filtre par titre)
        $toutes  = $seanceRepo->findSeancesAVenir();
        $filtrees = array_filter($toutes, fn($s) =>
            stripos($s->getTitre(), $programme->getTitre()) !== false
        );

        $data = array_values(array_map(fn($s) => [
            'id'          => $s->getId(),
            'titre'       => $s->getTitre(),
            'description' => $s->getDescription(),
            'lieu'        => $s->getLieu(),
            'date_debut'  => $s->getDateDebut()?->format('Y-m-d H:i'),
            'date_fin'    => $s->getDateFin()?->format('Y-m-d H:i'),
            'capacite'    => $s->getCapacite(),
            'image'       => $s->getImage(),
        ], $filtrees));

        return $this->json([
            'programme_id'    => $programme->getId(),
            'programme_titre' => $programme->getTitre(),
            'total_seances'   => count($data),
            'seances'         => $data,
        ]);
    }

    // =========================================================================
    // 12. RECOMMANDATION IA   GET /api/programmes/recommandations
    // =========================================================================
    #[Route('/recommandations', name: 'recommandations', methods: ['GET'])]
    public function recommandations(
        ProgrammeRepository $repo,
        FavoriProgrammeRepository $favRepo,
        CategorieRepository $catRepo,
        GeminiService $gemini,
        SessionInterface $session
    ): JsonResponse {
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // Programmes favoris de l'utilisateur
        $favoris    = $favRepo->findByUtilisateur($userId);
        $allPrograms = $repo->findAll();

        $categories = [];
        foreach ($catRepo->findAll() as $c) {
            $categories[$c->getId()] = $c->getNom();
        }

        // IDs déjà favoris
        $favoriIds = array_map(fn($f) => $f->getIdProgramme(), $favoris);

        // Construire le contexte pour Gemini
        $contexteFavoris = '';
        foreach ($favoris as $f) {
            $p = $repo->find($f->getIdProgramme());
            if ($p) {
                $contexteFavoris .= "- " . $p->getTitre() . " (" . ($categories[$p->getIdCategorie()] ?? 'N/A') . "): {$p->getDescription()}\n";
            }
        }

        $contexteTous = '';
        foreach ($allPrograms as $p) {
            if (!in_array($p->getId(), $favoriIds)) {
                $contexteTous .= "- ID:" . $p->getId() . " | " . $p->getTitre() . " (" . ($categories[$p->getIdCategorie()] ?? 'N/A') . "): " . $p->getDescription() . "\n";
            }
        }

        if (empty($contexteFavoris)) {
            // Pas de favoris → recommander les plus populaires
            $populaires = [];
            foreach ($allPrograms as $p) {
                $populaires[] = [
                    'id'         => $p->getId(),
                    'titre'      => $p->getTitre(),
                    'categorie'  => $categories[$p->getIdCategorie()] ?? 'N/A',
                    'nb_favoris' => $favRepo->countByProgramme($p->getId()),
                ];
            }
            usort($populaires, fn($a, $b) => $b['nb_favoris'] - $a['nb_favoris']);
            return $this->json([
                'source'          => 'popularite',
                'message'         => 'Aucun favori — voici les programmes les plus populaires.',
                'recommandations' => array_slice($populaires, 0, 5),
            ]);
        }

        // Appel Gemini pour recommandations intelligentes
        $prompt = "Voici les programmes que l'utilisateur aime :\n{$contexteFavoris}\n\n"
                . "Voici les autres programmes disponibles :\n{$contexteTous}\n\n"
                . "Recommande 3 programmes de la liste 'disponibles' qui correspondent le mieux aux goûts de l'utilisateur. "
                . "Réponds uniquement avec un JSON valide : {\"recommandations\":[{\"id\":1,\"raison\":\"...\"}, ...]}";

        try {
            $aiResponse = $gemini->chatGeneral($prompt);

            // Extraire le JSON de la réponse IA
            preg_match('/\{.*\}/s', $aiResponse, $matches);
            $aiJson = json_decode($matches[0] ?? '{}', true);

            $result = [];
            foreach (($aiJson['recommandations'] ?? []) as $r) {
                $p = $repo->find($r['id'] ?? 0);
                if ($p) {
                    $result[] = [
                        'id'        => $p->getId(),
                        'titre'     => $p->getTitre(),
                        'categorie' => $categories[$p->getIdCategorie()] ?? 'N/A',
                        'image'     => $p->getImage(),
                        'raison_ia' => $r['raison'] ?? '',
                    ];
                }
            }

            return $this->json([
                'source'          => 'ia_gemini',
                'recommandations' => $result,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'source'  => 'erreur_ia',
                'message' => 'Erreur IA : ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // 13. GÉNÉRATION DESCRIPTION IA   POST /api/programmes/{id}/generer-description
    // =========================================================================
    #[Route('/{id}/generer-description', name: 'generer_description', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function genererDescription(
        Programme $programme,
        GeminiService $gemini,
        EntityManagerInterface $em,
        CategorieRepository $catRepo,
        SessionInterface $session
    ): JsonResponse {
        if ($session->get('user_role') !== 'admin') {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $catNom = 'Bien-être';
        foreach ($catRepo->findAll() as $c) {
            if ($c->getId() === $programme->getIdCategorie()) {
                $catNom = $c->getNom();
                break;
            }
        }

        $prompt = "Génère une description attrayante et professionnelle en français pour un programme de bien-être mental "
                . "intitulé \"{$programme->getTitre()}\" dans la catégorie \"{$catNom}\". "
                . "La description doit faire 2-3 phrases, être motivante et expliquer les bénéfices.";

        try {
            $description = $gemini->chatGeneral($prompt);
            // Nettoyer la description
            $description = strip_tags(trim($description));

            // Sauvegarder automatiquement
            $programme->setDescription($description);
            $em->flush();

            return $this->json([
                'message'     => 'Description générée et sauvegardée',
                'description' => $description,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur IA : ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // 14. EXPORT PDF   GET /api/programmes/{id}/pdf
    // =========================================================================
    #[Route('/{id}/pdf', name: 'pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function exportPdf(
        Programme $programme,
        CategorieRepository $catRepo,
        FavoriProgrammeRepository $favRepo,
        SeanceRepository $seanceRepo,
        SessionInterface $session
    ): Response {
        if (!$session->get('user_id')) {
            return new Response('Non authentifié', 401);
        }

        // Catégorie
        $catNom = 'Non défini';
        foreach ($catRepo->findAll() as $c) {
            if ($c->getId() === $programme->getIdCategorie()) {
                $catNom = $c->getNom();
                break;
            }
        }

        // Séances liées
        $toutes   = $seanceRepo->findSeancesAVenir();
        $seances  = array_filter($toutes, fn($s) =>
            stripos($s->getTitre(), $programme->getTitre()) !== false
        );

        $nbFavoris = $favRepo->countByProgramme($programme->getId());

        // HTML du PDF
        $seancesHtml = '';
        foreach ($seances as $s) {
            $seancesHtml .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%d places</td></tr>',
                htmlspecialchars($s->getTitre()),
                $s->getDateDebut()?->format('d/m/Y H:i'),
                htmlspecialchars($s->getLieu()),
                $s->getCapacite()
            );
        }

        $imageHtml = '';
        if ($programme->getImage()) {
            $imgPath  = $this->getParameter('kernel.project_dir') . '/public/' . $programme->getImage();
            if (file_exists($imgPath)) {
                $imgData  = base64_encode(file_get_contents($imgPath));
                $imgMime  = mime_content_type($imgPath);
                $imageHtml = "<img src=\"data:{$imgMime};base64,{$imgData}\" style=\"max-width:300px;border-radius:8px;\">";
            }
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, Arial, sans-serif; color: #333; margin: 40px; }
  h1   { color: #2e86ab; border-bottom: 2px solid #2e86ab; padding-bottom: 8px; }
  .badge { background: #2e86ab; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
  table { width: 100%; border-collapse: collapse; margin-top: 20px; }
  th    { background: #2e86ab; color: white; padding: 8px; text-align: left; }
  td    { padding: 8px; border-bottom: 1px solid #eee; }
  .meta { color: #666; font-size: 13px; margin: 8px 0; }
  .desc { background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 16px 0; line-height: 1.6; }
  .footer { margin-top: 40px; text-align: center; color: #999; font-size: 11px; }
</style>
</head>
<body>
<h1>📋 {$programme->getTitre()}</h1>
<p class="meta">
  <span class="badge">{$catNom}</span>&nbsp;&nbsp;
  ❤️ {$nbFavoris} favoris &nbsp;&nbsp;
  🗓️ Généré le : {$this->formatDate(new \DateTime())}
</p>
{$imageHtml}
<div class="desc">
  <strong>Description :</strong><br>
  {$programme->getDescription()}
</div>

<h2>📅 Séances à venir</h2>
HTML;

        if ($seancesHtml) {
            $html .= "<table><thead><tr><th>Titre</th><th>Date</th><th>Lieu</th><th>Capacité</th></tr></thead><tbody>{$seancesHtml}</tbody></table>";
        } else {
            $html .= "<p><em>Aucune séance à venir pour ce programme.</em></p>";
        }

        $html .= <<<HTML
<div class="footer">
  MindGrow — Plateforme de bien-être mental | Document généré automatiquement
</div>
</body>
</html>
HTML;

        // Générer le PDF avec dompdf
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        $filename = 'programme_' . $programme->getId() . '_' . date('Ymd') . '.pdf';

        return new Response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // =========================================================================
    // 15. TRADUCTION   POST /api/programmes/{id}/traduire
    // =========================================================================
    #[Route('/{id}/traduire', name: 'traduire', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function traduire(
        Programme $programme,
        Request $request,
        GeminiService $gemini,
        SessionInterface $session
    ): JsonResponse {
        if (!$session->get('user_id')) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data   = json_decode($request->getContent(), true);
        $langue = $data['langue'] ?? 'en'; // en, ar, de, es, it...
        $langueNom = match($langue) {
            'en' => 'anglais', 'ar' => 'arabe', 'de' => 'allemand',
            'es' => 'espagnol', 'it' => 'italien', default => $langue,
        };

        $prompt = "Traduis ce contenu en {$langueNom}. Réponds uniquement avec le JSON suivant :\n"
                . "{\"titre\":\"...\",\"description\":\"...\"}\n\n"
                . "Titre original : {$programme->getTitre()}\n"
                . "Description originale : {$programme->getDescription()}";

        try {
            $aiResponse = $gemini->chatGeneral($prompt);
            preg_match('/\{.*\}/s', $aiResponse, $matches);
            $result = json_decode($matches[0] ?? '{}', true);

            return $this->json([
                'langue'      => $langue,
                'titre'       => $result['titre'] ?? '',
                'description' => $result['description'] ?? '',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur traduction : ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // HELPER — Sérialisation d'un programme en tableau
    // =========================================================================
    private function serializeProgramme(
        Programme $p,
        array $categories,
        FavoriProgrammeRepository $favRepo,
        int $userId
    ): array {
        return [
            'id'          => $p->getId(),
            'titre'       => $p->getTitre(),
            'description' => $p->getDescription(),
            'image'       => $p->getImage(),
            'video'       => $p->getVideo(),
            'categorie'   => [
                'id'  => $p->getIdCategorie(),
                'nom' => $categories[$p->getIdCategorie()] ?? 'Inconnu',
            ],
            'nb_favoris'  => $favRepo->countByProgramme($p->getId()),
            'est_favori'  => $favRepo->isFavori($userId, $p->getId()),
        ];
    }

    private function formatDate(\DateTimeInterface $dt): string
    {
        return $dt->format('d/m/Y à H:i');
    }
}
