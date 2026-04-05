<?php

namespace App\Controller\Front;

use App\Entity\Avis;
use App\Form\AvisType;
use App\Repository\TherapeuteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/therapeutes')]
class TherapeuteFrontController extends AbstractController
{
    #[Route('/', name: 'front_therapeute_index')]
    public function index(TherapeuteRepository $repo, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $specialite = $request->query->get('specialite', '');
        $therapeutes = $repo->findFiltered($search, $specialite);
        $specialites = $repo->findAllSpecialites();
        return $this->render('front/therapeute/index.html.twig', [
            'therapeutes' => $therapeutes,
            'specialites' => $specialites,
            'search' => $search,
            'selectedSpec' => $specialite,
        ]);
    }

    #[Route('/goto-admin', name: 'front_goto_admin')]
    public function gotoAdmin(): Response
    {
        // Directly redirect to the admin therapist management page.
        // Remove role check for development/testing only.
        return $this->redirectToRoute('admin_therapeute_index');
    }

    #[Route('/{id}', name: 'front_therapeute_show', methods: ['GET', 'POST'])]
    public function show(int $id, TherapeuteRepository $repo, Request $request, EntityManagerInterface $em): Response
    {
        $therapeute = $repo->find($id);
        if (!$therapeute) throw $this->createNotFoundException();
        $avis = new Avis();
        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $avis->setTherapeute($therapeute);
            $avis->setIdUtilisateur(1);
            $em->persist($avis);
            $em->flush();
            $this->addFlash('success', 'Merci pour votre avis !');
            return $this->redirectToRoute('front_therapeute_show', ['id' => $id]);
        }
        return $this->render('front/therapeute/show.html.twig', [
            'therapeute' => $therapeute,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/avis', name: 'front_therapeute_avis', methods: ['GET', 'POST'])]
    public function avis(int $id, TherapeuteRepository $repo, Request $request, EntityManagerInterface $em): Response
    {
        $therapeute = $repo->find($id);
        if (!$therapeute) throw $this->createNotFoundException();
        $avis = new Avis();
        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $avis->setTherapeute($therapeute);
            $avis->setIdUtilisateur(1);
            $em->persist($avis);
            $em->flush();
            $this->addFlash('success', 'Merci pour votre avis !');
            return $this->redirectToRoute('front_therapeute_index');
        }
        return $this->render('front/therapeute/avis.html.twig', [
            'therapeute' => $therapeute,
            'form' => $form->createView(),
        ]);
    }
}