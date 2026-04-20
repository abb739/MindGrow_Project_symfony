<?php
namespace App\Controller;

use App\Entity\Avis;
use App\Repository\AvisRepository;
use App\Repository\TherapeuteRepository;
use App\Service\WordFilterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class AvisController extends AbstractController
{
    #[Route('/avis/new/{idTherapeute}', name: 'avis_new', methods: ['POST'])]
    public function new(Request $request, int $idTherapeute, EntityManagerInterface $em, AvisRepository $avisRepo, SessionInterface $session, WordFilterService $wordFilter): Response
    {
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest'
               || str_contains($request->headers->get('Accept',''), 'application/json');

        if (!$session->get('user_id')) {
            if ($isAjax) return $this->json(['error' => 'Non connecté'], 401);
            return $this->redirectToRoute('login');
        }

        $note        = (int)$request->request->get('note');
        $commentaire = trim((string)$request->request->get('commentaire', ''));

        if ($note < 1 || $note > 5) {
            if ($isAjax) return $this->json(['error' => 'Note invalide (1 à 5)'], 422);
            return $this->redirectToRoute('therapeutes');
        }

        if ($commentaire !== '' && $wordFilter->containsBadWords($commentaire)) {
            if ($isAjax) return $this->json(['error' => 'Commentaire contient des mots inappropriés.'], 422);
            $this->addFlash('error', 'Votre commentaire contient des mots inappropriés.');
            return $this->redirectToRoute('therapeutes');
        }

        $avis = new Avis();
        $avis->setIdTherapeute($idTherapeute);
        $avis->setIdUtilisateur($session->get('user_id'));
        $avis->setNote($note);
        $avis->setCommentaire($commentaire !== '' ? $wordFilter->filterText($commentaire) : null);
        $avis->setDateAvis(new \DateTime());
        $em->persist($avis);
        $em->flush();

        $newMoyenne = $avisRepo->getNoteMoyenne($idTherapeute);
        $newCount   = count($avisRepo->findByTherapeute($idTherapeute));

        if ($isAjax) {
            return $this->json([
                'success'  => true,
                'note'     => $note,
                'commentaire' => $avis->getCommentaire(),
                'date'     => $avis->getDateAvis()->format('d/m/Y'),
                'userId'   => $session->get('user_id'),
                'moyenne'  => $newMoyenne,
                'nbAvis'   => $newCount,
            ]);
        }

        return $this->redirectToRoute('therapeutes');
    }

    #[Route('/avis/edit/{id}', name: 'avis_edit', methods: ['POST'])]
    public function edit(Request $request, Avis $avis, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');
        if ($avis->getIdUtilisateur() !== $session->get('user_id')) {
            return $this->redirectToRoute('therapeutes');
        }

        $note = (int)$request->request->get('note');
        $commentaire = $request->request->get('commentaire');

        if ($note >= 1 && $note <= 5 && !empty($commentaire)) {
            $avis->setNote($note);
            $avis->setCommentaire($commentaire);
            $em->flush();
        }

        return $this->redirectToRoute('therapeutes');
    }

    #[Route('/avis/delete/{id}', name: 'avis_delete')]
    public function delete(Avis $avis, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');
        if ($avis->getIdUtilisateur() !== $session->get('user_id') && $session->get('user_role') !== 'admin') {
            return $this->redirectToRoute('therapeutes');
        }
        $em->remove($avis);
        $em->flush();
        return $this->redirectToRoute('therapeutes');
    }

    // BACKOFFICE ADMIN
    #[Route('/admin/avis', name: 'admin_avis')]
    public function adminIndex(AvisRepository $repo, TherapeuteRepository $therapeuteRepo, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        $avisList = $repo->findAll();
        $therapeutes = [];
        foreach ($avisList as $a) {
            $therapeutes[$a->getId()] = $therapeuteRepo->find($a->getIdTherapeute());
        }
        return $this->render('admin/avis.html.twig', [
            'avisList' => $avisList,
            'therapeutes' => $therapeutes,
        ]);
    }
}