<?php
namespace App\Controller;

use App\Entity\Programme;
use App\Entity\Categorie;
use App\Repository\ProgrammeRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProgrammeController extends AbstractController
{
    private const IMG_DIR = 'uploads/programmes/images/';
    private const VID_DIR = 'uploads/programmes/videos/';
    private const IMG_EXT = ['jpg','jpeg','png','gif','webp'];
    private const VID_EXT = ['mp4','webm','ogg','mov'];

    private function uploadFile(Request $request, string $field, string $subDir, array $allowed, string $publicDir): ?string
    {
        $file = $request->files->get($field);
        if (!$file || !$file->isValid()) return null;
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, $allowed)) return null;
        $filename = uniqid('prog_', true) . '.' . $ext;
        $destDir  = $publicDir . $subDir;
        if (!is_dir($destDir)) mkdir($destDir, 0775, true);
        $file->move($destDir, $filename);
        return $subDir . $filename;
    }

    // ✅ CORRECTION 3 : Utilisation de $request->query au lieu de $_GET
    #[Route('/programmes', name: 'programmes')]
    public function index(Request $request, ProgrammeRepository $repo, CategorieRepository $catRepo, SessionInterface $session): Response
    {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');

        $search = $request->query->get('search', '');
        $catId  = $request->query->get('categorie', '');

        $programmes = ($search || $catId) ? $repo->findByFilters($search, $catId) : $repo->findAll();

        return $this->render('client/programme_index.html.twig', [
            'programmes' => $programmes,
            'categories' => $catRepo->findAll(),
            'search'     => $search,
            'catId'      => $catId,
            'favoris'    => [],
        ]);
    }

    #[Route('/programmes/{id}', name: 'programme_show', requirements: ['id' => '\d+'])]
    public function show(Programme $programme, CategorieRepository $catRepo, SessionInterface $session): Response
    {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');
        $catNom = 'Sans catégorie';
        foreach ($catRepo->findAll() as $cat) {
            if ($cat->getId() === $programme->getIdCategorie()) { $catNom = $cat->getNom(); break; }
        }
        return $this->render('client/programme_show.html.twig', [
            'programme' => $programme,
            'catNom'    => $catNom,
            'favoris'   => [],
        ]);
    }

    #[Route('/admin/programmes', name: 'admin_programmes')]
    public function adminIndex(ProgrammeRepository $repo, CategorieRepository $catRepo, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        return $this->render('admin/programme.html.twig', [
            'programmes' => $repo->findAll(),
            'categories' => $catRepo->findAll(),
        ]);
    }

    #[Route('/admin/programmes/new', name: 'admin_programme_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        // ✅ CORRECTION PRINCIPALE : Détecter si PHP a rejeté le POST car fichier trop lourd.
        // Quand post_max_size est dépassé, $_POST et $_FILES sont vides mais CONTENT_LENGTH est grand.
        $contentLength = (int)$request->server->get('CONTENT_LENGTH', 0);
        $postMaxSize   = $this->parseSize(ini_get('post_max_size'));
        if ($contentLength > $postMaxSize) {
            $this->addFlash('error', 'Fichier trop volumineux ! Limite serveur : ' . ini_get('post_max_size') . '. Réduisez la taille de la vidéo ou de l\'image.');
            return $this->redirectToRoute('admin_programmes');
        }

        $titre = $request->request->get('titre');
        $idCat = $request->request->get('idCategorie');

        // ✅ Messages d'erreur flash explicites au lieu de redirections silencieuses
        if (empty($titre)) {
            $this->addFlash('error', 'Le titre est obligatoire.');
            return $this->redirectToRoute('admin_programmes');
        }
        if (empty($idCat)) {
            $this->addFlash('error', 'La catégorie est obligatoire.');
            return $this->redirectToRoute('admin_programmes');
        }
        if (!preg_match('/^[a-zA-Z0-9\s\-\'À-ÿ]+$/u', $titre)) {
            $this->addFlash('error', 'Le titre contient des caractères non autorisés.');
            return $this->redirectToRoute('admin_programmes');
        }

        $publicDir = $this->getParameter('kernel.project_dir') . '/public/';
        $prog = new Programme();
        $prog->setTitre($titre);
        $prog->setDescription($request->request->get('description'));
        $prog->setIdCategorie((int)$idCat);
        $prog->setImage($this->uploadFile($request, 'image_file', self::IMG_DIR, self::IMG_EXT, $publicDir));
        $prog->setVideo($this->uploadFile($request, 'video_file', self::VID_DIR, self::VID_EXT, $publicDir));
        $em->persist($prog);
        $em->flush();

        $this->addFlash('success', 'Programme ajouté avec succès');
        return $this->redirectToRoute('admin_programmes');
    }

    #[Route('/admin/programmes/edit/{id}', name: 'admin_programme_edit', methods: ['POST'])]
    public function edit(Request $request, Programme $programme, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        // ✅ Détecter fichier trop lourd dans edit aussi
        $contentLength = (int)$request->server->get('CONTENT_LENGTH', 0);
        $postMaxSize   = $this->parseSize(ini_get('post_max_size'));
        if ($contentLength > $postMaxSize) {
            $this->addFlash('error', 'Fichier trop volumineux ! Limite serveur : ' . ini_get('post_max_size'));
            return $this->redirectToRoute('admin_programmes');
        }

        // ✅ CORRECTION 5 : Validation du titre dans edit avec regex
        $titre = $request->request->get('titre');
        if (!empty($titre)) {
            if (!preg_match('/^[a-zA-Z0-9\s\-\'À-ÿ]+$/u', $titre)) {
                $this->addFlash('error', 'Titre invalide : caractères non autorisés.');
                return $this->redirectToRoute('admin_programmes');
            }
            $programme->setTitre($titre);
        }

        // ✅ CORRECTION 4 : Ne pas écraser la description si le champ est vide
        $desc = $request->request->get('description');
        if ($desc !== null && $desc !== '') {
            $programme->setDescription($desc);
        }

        $idCat = $request->request->get('idCategorie');
        if (!empty($idCat)) {
            $programme->setIdCategorie((int)$idCat);
        }

        $publicDir = $this->getParameter('kernel.project_dir') . '/public/';

        $img = $this->uploadFile($request, 'image_file', self::IMG_DIR, self::IMG_EXT, $publicDir);
        if ($img) {
            if ($programme->getImage()) @unlink($publicDir . $programme->getImage());
            $programme->setImage($img);
        }

        $vid = $this->uploadFile($request, 'video_file', self::VID_DIR, self::VID_EXT, $publicDir);
        if ($vid) {
            if ($programme->getVideo()) @unlink($publicDir . $programme->getVideo());
            $programme->setVideo($vid);
        }

        $em->flush();
        $this->addFlash('success', 'Programme modifié avec succès');
        return $this->redirectToRoute('admin_programmes');
    }

    // ✅ CORRECTION 2 : Suppression en POST avec CSRF
    #[Route('/admin/programmes/delete/{id}', name: 'admin_programme_delete', methods: ['POST'])]
    public function delete(Request $request, Programme $programme, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        if (!$this->isCsrfTokenValid('delete-prog-' . $programme->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_programmes');
        }

        $publicDir = $this->getParameter('kernel.project_dir') . '/public/';
        if ($programme->getImage()) @unlink($publicDir . $programme->getImage());
        if ($programme->getVideo()) @unlink($publicDir . $programme->getVideo());
        $em->remove($programme);
        $em->flush();

        $this->addFlash('success', 'Programme supprimé avec succès');
        return $this->redirectToRoute('admin_programmes');
    }

    #[Route('/admin/categories/new', name: 'admin_categorie_new', methods: ['POST'])]
    public function newCategorie(Request $request, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        $nom = $request->request->get('nom');
        if (empty($nom) || !preg_match('/^[a-zA-ZÀ-ÿ\s]+$/u', $nom)) {
            $this->addFlash('error', empty($nom) ? 'Le nom est obligatoire.' : 'Le nom ne doit contenir que des lettres.');
            return $this->redirectToRoute('admin_programmes');
        }
        $cat = new Categorie();
        $cat->setNom($nom);
        $cat->setDescription($request->request->get('description'));
        $em->persist($cat);
        $em->flush();
        $this->addFlash('success', 'Catégorie ajoutée avec succès');
        return $this->redirectToRoute('admin_programmes');
    }

    // ✅ CORRECTION 2 : Suppression catégorie en POST avec CSRF
    #[Route('/admin/categories/delete/{id}', name: 'admin_categorie_delete', methods: ['POST'])]
    public function deleteCategorie(Request $request, Categorie $categorie, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        if (!$this->isCsrfTokenValid('delete-cat-' . $categorie->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_programmes');
        }

        $em->remove($categorie);
        $em->flush();
        $this->addFlash('success', 'Catégorie supprimée avec succès');
        return $this->redirectToRoute('admin_programmes');
    }

    /**
     * Convertit "8M", "128K", "1G" en octets
     */
    private function parseSize(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $val  = (int)$size;
        switch ($last) {
            case 'g': $val *= 1024;
            // no break
            case 'm': $val *= 1024;
            // no break
            case 'k': $val *= 1024;
        }
        return $val;
    }
}