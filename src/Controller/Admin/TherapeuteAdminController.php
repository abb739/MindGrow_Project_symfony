<?php

namespace App\Controller\Admin;

use App\Entity\Therapeute;
use App\Form\TherapeuteType;
use App\Repository\TherapeuteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/therapeutes')]
class TherapeuteAdminController extends AbstractController
{
    #[Route('/', name: 'admin_therapeute_index')]
    public function index(TherapeuteRepository $repo, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'desc');
        $therapeutes = $search ? $repo->search($search, $sort) : $repo->findAllSorted($sort);
        return $this->render('admin/therapeute/index.html.twig', [
            'therapeutes' => $therapeutes,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/new', name: 'admin_therapeute_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $therapeute = new Therapeute();
        $form = $this->createForm(TherapeuteType::class, $therapeute);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleFileUploads($form, $therapeute, $slugger);
            $em->persist($therapeute);
            $em->flush();
            $this->addFlash('success', 'Therapeute ajoute !');
            return $this->redirectToRoute('admin_therapeute_index');
        }
        return $this->render('admin/therapeute/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Nouveau Therapeute',
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_therapeute_edit', methods: ['GET', 'POST'])]
    public function edit(Therapeute $therapeute, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(TherapeuteType::class, $therapeute);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleFileUploads($form, $therapeute, $slugger);
            $em->flush();
            $this->addFlash('success', 'Therapeute modifie !');
            return $this->redirectToRoute('admin_therapeute_index');
        }
        return $this->render('admin/therapeute/form.html.twig', [
            'form' => $form->createView(),
            'therapeute' => $therapeute,
            'title' => 'Modifier le Therapeute',
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_therapeute_delete', methods: ['POST'])]
    public function delete(Therapeute $therapeute, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete' . $therapeute->getId(), $request->request->get('_token'))) {
            $em->remove($therapeute);
            $em->flush();
            $this->addFlash('success', 'Therapeute supprime.');
        }
        return $this->redirectToRoute('admin_therapeute_index');
    }

    private function handleFileUploads($form, Therapeute $therapeute, SluggerInterface $slugger): void
    {
        $imageFile = $form->get('imageFile')->getData();
        if ($imageFile) {
            $newFilename = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move($this->getParameter('uploads_directory'), $newFilename);
            $therapeute->setImage($newFilename);
        }
        $certificatFile = $form->get('certificatFile')->getData();
        if ($certificatFile) {
            $newFilename = $slugger->slug(pathinfo($certificatFile->getClientOriginalName(), PATHINFO_FILENAME)) . '-' . uniqid() . '.' . $certificatFile->guessExtension();
            $certificatFile->move($this->getParameter('uploads_directory'), $newFilename);
            $therapeute->setCertificat($newFilename);
        }
    }
}