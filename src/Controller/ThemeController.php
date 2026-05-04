<?php

namespace App\Controller;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API Thème — Sauvegarde et récupération de la préférence dark/light
 * Métier avancé : persistance BDD + sync multi-appareils + détection OS
 */
class ThemeController extends AbstractController
{
    // ──────────────────────────────────────────────
    // GET — Récupérer le thème de l'utilisateur connecté
    // ──────────────────────────────────────────────
    #[Route('/api/theme', name: 'api_theme_get', methods: ['GET'])]
    public function getTheme(
        SessionInterface      $session,
        UtilisateurRepository $repo
    ): JsonResponse {
        $userId = $session->get('user_id');

        if (!$userId) {
            return $this->json(['theme' => 'auto', 'source' => 'guest']);
        }

        $user = $repo->find($userId);
        $theme = $user?->getThemePreference() ?? 'auto';

        return $this->json([
            'theme'  => $theme,
            'source' => 'database',
            'user'   => $session->get('user_nom'),
        ]);
    }

    // ──────────────────────────────────────────────
    // POST — Sauvegarder le thème en BDD
    // ──────────────────────────────────────────────
    #[Route('/api/theme', name: 'api_theme_set', methods: ['POST'])]
    public function setTheme(
        Request               $request,
        SessionInterface      $session,
        UtilisateurRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        $userId = $session->get('user_id');

        $data  = json_decode($request->getContent(), true);
        $theme = $data['theme'] ?? 'auto';

        // Valider la valeur
        if (!in_array($theme, ['light', 'dark', 'auto'])) {
            return $this->json(['error' => 'Valeur invalide'], 400);
        }

        // Sauvegarder en BDD si connecté
        if ($userId) {
            $user = $repo->find($userId);
            if ($user) {
                $user->setThemePreference($theme);
                $em->flush();
            }
        }

        return $this->json([
            'success' => true,
            'theme'   => $theme,
            'saved'   => $userId ? 'database' : 'local_only',
        ]);
    }
}
