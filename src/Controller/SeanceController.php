<?php
namespace App\Controller;

use App\Entity\Seance;
use App\Repository\ReservationRepository;
use App\Repository\SeanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class SeanceController extends AbstractController
{
    #[Route('/seances', name: 'seances')]
    public function index(Request $request, SeanceRepository $repo, ReservationRepository $reservationRepo, SessionInterface $session): Response
    {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');

        $search = $request->query->get('search', '');
        $filtre = $request->query->get('filtre', 'toutes');

        if ($search) {
            $seances = $repo->findBySearch($search);
        } elseif ($filtre === 'avenir') {
            $seances = $repo->findSeancesAVenir();
        } else {
            $seances = $repo->findAll();
        }

        $placesRestantes = [];
        $userId = $session->get('user_id');
        $dejaReserve = [];

        foreach ($seances as $s) {
            $reservationsActives = array_filter(
                $reservationRepo->findBy(['idSeance' => $s->getId()]),
                fn($r) => $r->getStatut() !== 'annulée'
            );
            $placesRestantes[$s->getId()] = $s->getCapacite() - count($reservationsActives);

            $reservationUser = $reservationRepo->findOneBy([
                'idSeance' => $s->getId(),
                'idUtilisateur' => $userId
            ]);
            $dejaReserve[$s->getId()] = $reservationUser && $reservationUser->getStatut() !== 'annulée';
        }

        return $this->render('seance/index.html.twig', [
            'seances' => $seances,
            'placesRestantes' => $placesRestantes,
            'dejaReserve' => $dejaReserve,
            'search' => $search,
            'filtre' => $filtre,
        ]);
    }

    #[Route('/admin/seances', name: 'admin_seances')]
    public function adminIndex(Request $request, SeanceRepository $repo, ReservationRepository $reservationRepo, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        $tri = $request->query->get('tri', 'date');
        $search = $request->query->get('search', '');

        if ($search) {
            $seances = $repo->findBySearch($search);
        } elseif ($tri === 'capacite_desc') {
            $seances = $repo->findAllOrderedByCapacite('DESC');
        } elseif ($tri === 'capacite_asc') {
            $seances = $repo->findAllOrderedByCapacite('ASC');
        } else {
            $seances = $repo->findAll();
        }

        $statsReservations = [];
        foreach ($seances as $s) {
            $reservationsActives = array_filter(
                $reservationRepo->findBy(['idSeance' => $s->getId()]),
                fn($r) => $r->getStatut() !== 'annulée'
            );
            $statsReservations[$s->getId()] = [
                'nb' => count($reservationsActives),
                'taux' => $s->getCapacite() > 0 ? round((count($reservationsActives) / $s->getCapacite()) * 100) : 0,
            ];
        }

        return $this->render('seance/admin.html.twig', [
            'seances' => $seances,
            'statsReservations' => $statsReservations,
            'tri' => $tri,
            'search' => $search,
        ]);
    }

    #[Route('/admin/seances/new', name: 'admin_seance_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        $titre       = trim($request->request->get('titre', ''));
        $lieu        = trim($request->request->get('lieu', ''));
        $description = trim($request->request->get('description', ''));
        $capacite    = $request->request->get('capacite');
        $dateDebut   = $request->request->get('dateDebut');
        $dateFin     = $request->request->get('dateFin');

        $errors = [];

        if (empty($titre)) {
            $errors[] = 'Le titre est obligatoire';
        } elseif (strlen($titre) < 3) {
            $errors[] = 'Le titre doit avoir au moins 3 caractères';
        } elseif (strlen($titre) > 100) {
            $errors[] = 'Le titre ne doit pas dépasser 100 caractères';
        }

        if (empty($lieu)) $errors[] = 'Le lieu est obligatoire';

        if ($capacite === '' || $capacite === null) {
            $errors[] = 'La capacité est obligatoire';
        } elseif (!is_numeric($capacite) || (int)$capacite <= 0) {
            $errors[] = 'La capacité doit être un entier positif';
        } elseif ((int)$capacite > 10000) {
            $errors[] = 'La capacité ne peut pas dépasser 10 000';
        }

        if (empty($dateDebut)) $errors[] = 'La date de début est obligatoire';
        if (empty($dateFin))   $errors[] = 'La date de fin est obligatoire';

        if (empty($errors) && !empty($dateDebut) && !empty($dateFin)) {
            $debut = new \DateTime($dateDebut);
            $fin   = new \DateTime($dateFin);
            if ($fin <= $debut) {
                $errors[] = 'La date de fin doit être après la date de début';
            }
            if ($debut < new \DateTime()) {
                $errors[] = 'La date de début ne peut pas être dans le passé';
            }
        }

        // ✅ Validation image AVANT de créer la séance
        $imageFile = $request->files->get('image');
        $imagePath = null;
        if ($imageFile) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($imageFile->getMimeType(), $allowedMimes)) {
                $errors[] = 'Format image invalide — JPG, PNG ou WEBP requis';
            } elseif ($imageFile->getSize() > 2 * 1024 * 1024) {
                $errors[] = 'Image trop lourde — maximum 2 Mo';
            }
        }

        if (!empty($errors)) {
            $seances = $em->getRepository(Seance::class)->findAll();
            return $this->render('seance/admin.html.twig', [
                'seances' => $seances,
                'errors' => $errors,
                'statsReservations' => [],
                'tri' => 'date',
                'search' => '',
            ]);
        }

        // 🖼️ Upload image
        if ($imageFile) {
            $nomFichier = uniqid('seance_') . '.' . $imageFile->guessExtension();
            $imageFile->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads/seances',
                $nomFichier
            );
            $imagePath = '/uploads/seances/' . $nomFichier;
        }

        $seance = new Seance();
        $seance->setTitre($titre);
        $seance->setDescription($description);
        $seance->setLieu($lieu);
        $seance->setCapacite((int)$capacite);
        $seance->setDateDebut(new \DateTime($dateDebut));
        $seance->setDateFin(new \DateTime($dateFin));
        if ($imagePath) $seance->setImage($imagePath);

        $em->persist($seance);
        $em->flush();

        return $this->redirectToRoute('admin_seances');
    }

    #[Route('/admin/seances/edit/{id}', name: 'admin_seance_edit', methods: ['POST'])]
    public function edit(Request $request, Seance $seance, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        $titre     = trim($request->request->get('titre', ''));
        $lieu      = trim($request->request->get('lieu', ''));
        $capacite  = $request->request->get('capacite');
        $dateDebut = $request->request->get('dateDebut');
        $dateFin   = $request->request->get('dateFin');

        $errors = [];
        if (empty($titre) || strlen($titre) < 3) $errors[] = 'Le titre doit avoir au moins 3 caractères';
        if (empty($lieu)) $errors[] = 'Le lieu est obligatoire';
        if (!is_numeric($capacite) || (int)$capacite <= 0) $errors[] = 'La capacité doit être un entier positif';
        if (!empty($dateDebut) && !empty($dateFin)) {
            if (new \DateTime($dateFin) <= new \DateTime($dateDebut)) {
                $errors[] = 'La date de fin doit être après la date de début';
            }
        }

        if (!empty($errors)) return $this->redirectToRoute('admin_seances');

        $seance->setTitre($titre);
        $seance->setDescription($request->request->get('description'));
        $seance->setLieu($lieu);
        $seance->setCapacite((int)$capacite);
        $seance->setDateDebut(new \DateTime($dateDebut));
        $seance->setDateFin(new \DateTime($dateFin));

        // 🖼️ Changer l'image si une nouvelle est uploadée
        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            if (in_array($imageFile->getMimeType(), $allowedMimes) && $imageFile->getSize() <= 2 * 1024 * 1024) {
                // Supprimer l'ancienne image
                if ($seance->getImage() && str_starts_with($seance->getImage(), '/uploads/')) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $seance->getImage();
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $nomFichier = uniqid('seance_') . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/seances',
                    $nomFichier
                );
                $seance->setImage('/uploads/seances/' . $nomFichier);
            }
        }

        $em->flush();
        return $this->redirectToRoute('admin_seances');
    }

    #[Route('/admin/seances/delete/{id}', name: 'admin_seance_delete')]
    public function delete(Seance $seance, EntityManagerInterface $em, SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        // Supprimer le fichier image si existant
        if ($seance->getImage() && str_starts_with($seance->getImage(), '/uploads/')) {
            $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $seance->getImage();
            if (file_exists($oldPath)) unlink($oldPath);
        }

        $em->remove($seance);
        $em->flush();
        return $this->redirectToRoute('admin_seances');
    }
}
