<?php

namespace App\Controller\Admin;

use App\Entity\Seance;
use App\Form\SeanceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/seances')]
#[IsGranted('ROLE_ADMIN')]
final class SeanceController extends AbstractController
{
   #[Route(name: 'app_admin_seance_index', methods: ['GET'])]
public function index(Request $request, EntityManagerInterface $entityManager): Response
{
    $titreSearch = $request->query->get('titre', '');
    $dateSearch = $request->query->get('date', '');

    $repo = $entityManager->getRepository(Seance::class);
    $qb = $repo->createQueryBuilder('s');

    // Filtre par titre
    if ($titreSearch) {
        $qb->andWhere('s.titre LIKE :titre')
           ->setParameter('titre', '%'.$titreSearch.'%');
    }

    // Filtre par date (corrigé)
    if ($dateSearch) {
        $date = \DateTime::createFromFormat('Y-m-d', $dateSearch);
        if ($date) {
            // Début et fin de journée
            $startOfDay = (clone $date)->setTime(0, 0, 0);
            $endOfDay = (clone $date)->setTime(23, 59, 59);

            $qb->andWhere('s.dateDebut BETWEEN :start AND :end')
               ->setParameter('start', $startOfDay)
               ->setParameter('end', $endOfDay);
        }
    }

    $qb->orderBy('s.dateDebut', 'ASC');
    $seances = $qb->getQuery()->getResult();

    return $this->render('admin/seance/index.html.twig', [
        'seances' => $seances,
        'titreSearch' => $titreSearch,
        'dateSearch' => $dateSearch,
    ]);
}



    #[Route('/new', name: 'app_admin_seance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $seance = new Seance();
        $form = $this->createForm(SeanceType::class, $seance);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Debug: check if form is valid
            if (!$form->isValid()) {
                $errors = $form->getErrors(true);
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }

            if ($form->isValid()) {
                $imageFile = $form->get('image')->getData();
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = preg_replace('/[^a-zA-Z0-9_]/', '_', $originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir').'/public/uploads/seances',
                            $newFilename
                        );
                        $seance->setImage($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                    }
                }

                $entityManager->persist($seance);
                $entityManager->flush();

                $this->addFlash('success', 'La séance a été créée avec succès.');
                return $this->redirectToRoute('app_admin_seance_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('admin/seance/new.html.twig', [
            'seance' => $seance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_seance_show', methods: ['GET'])]
    public function show(Seance $seance): Response
    {
        return $this->render('admin/seance/show.html.twig', [
            'seance' => $seance,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_seance_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Seance $seance, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SeanceType::class, $seance, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^a-zA-Z0-9_]/', '_', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/seances',
                        $newFilename
                    );
                } catch (FileException $e) {
                    // handle exception if something happens during file upload
                }

                $seance->setImage($newFilename);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_admin_seance_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/seance/edit.html.twig', [
            'seance' => $seance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_seance_delete', methods: ['POST'])]
    public function delete(Request $request, Seance $seance, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$seance->getId(), $request->request->get('_token'))) {
            $entityManager->remove($seance);
            $entityManager->flush();
            $this->addFlash('success', 'La séance "' . $seance->getTitre() . '" a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_admin_seance_index', [], Response::HTTP_SEE_OTHER);
    }
}
