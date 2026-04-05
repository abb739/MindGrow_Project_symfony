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
    #[Route('/programmes', name: 'programmes')]
    public function index(ProgrammeRepository $repo, CategorieRepository $catRepo, SessionInterface $session): Response
    {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');
        $search = $_GET['search'] ?? '';
        $catId = $_GET['categorie'] ?? '';
        $programmes = $search || $catId
            ? $repo->findByFilters($search, $catId)
            : $repo->findAll();
        return $this->render('programme/index.html.twig', [
            'programmes' => $programmes,
            'categories' => $catRepo->findAll(),
            'search' => $search,
            'catId' => $catId,
        ]);
    }

    // BACKOFFICE ADMIN
    #[Route('/admin/programmes', name: 'admin_programmes')]
    public function adminIndex(ProgrammeRepository $repo, CategorieRepository $catRepo, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        return $this->render('programme/admin.html.twig', [
            'programmes' => $repo->findAll(),
            'categories' => $catRepo->findAll(),
        ]);
    }

    #[Route('/admin/programmes/new', name: 'admin_programme_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em, CategorieRepository $catRepo, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        $titre = $request->request->get('titre');
        $idCat = $request->request->get('idCategorie');

        if (empty($titre) || empty($idCat)) {
            return $this->redirectToRoute('admin_programmes');
        }
        if (!preg_match('/^[a-zA-Z0-9\s]+$/', $titre)) {
            return $this->redirectToRoute('admin_programmes');
        }

        $prog = new Programme();
        $prog->setTitre($titre);
        $prog->setDescription($request->request->get('description'));
        $prog->setIdCategorie((int)$idCat);
        $prog->setImage($request->request->get('image'));
        $prog->setVideo($request->request->get('video'));
        $em->persist($prog);
        $em->flush();
        return $this->redirectToRoute('admin_programmes');
    }

    #[Route('/admin/programmes/edit/{id}', name: 'admin_programme_edit', methods: ['POST'])]
    public function edit(Request $request, Programme $programme, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        $programme->setTitre($request->request->get('titre'));
        $programme->setDescription($request->request->get('description'));
        $programme->setIdCategorie((int)$request->request->get('idCategorie'));
        $programme->setImage($request->request->get('image'));
        $programme->setVideo($request->request->get('video'));
        $em->flush();
        return $this->redirectToRoute('admin_programmes');
    }

    #[Route('/admin/programmes/delete/{id}', name: 'admin_programme_delete')]
    public function delete(Programme $programme, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        $em->remove($programme);
        $em->flush();
        return $this->redirectToRoute('admin_programmes');
    }

    // CATEGORIES
    #[Route('/admin/categories/new', name: 'admin_categorie_new', methods: ['POST'])]
    public function newCategorie(Request $request, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        $nom = $request->request->get('nom');
        if (empty($nom) || !preg_match('/^[a-zA-Z\s]+$/', $nom)) {
            return $this->redirectToRoute('admin_programmes');
        }
        $cat = new Categorie();
        $cat->setNom($nom);
        $cat->setDescription($request->request->get('description'));
        $em->persist($cat);
        $em->flush();
        return $this->redirectToRoute('admin_programmes');
    }

    #[Route('/admin/categories/delete/{id}', name: 'admin_categorie_delete')]
    public function deleteCategorie(Categorie $categorie, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        $em->remove($categorie);
        $em->flush();
        return $this->redirectToRoute('admin_programmes');
    }
}