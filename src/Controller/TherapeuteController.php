<?php
namespace App\Controller;

use App\Entity\Therapeute;
use App\Repository\AvisRepository;
use App\Repository\TherapeuteRepository;
use App\Service\GoogleVisionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class TherapeuteController extends AbstractController
{
    // ============================================================
    // FRONTOFFICE CLIENT
    // ============================================================

    #[Route('/therapeutes', name: 'therapeutes')]
    public function index(
        Request $request,
        TherapeuteRepository $repo,
        AvisRepository $avisRepo,
        SessionInterface $session
    ): Response {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');

        $search     = $request->query->get('search', '');
        $specialite = $request->query->get('specialite', '');

        $therapeutes = $repo->findBySearch($search, $specialite);

        $noteMoyenne = [];
        $nbAvis      = [];
        $avisList    = [];
        foreach ($therapeutes as $t) {
            $noteMoyenne[$t->getId()] = $avisRepo->getNoteMoyenne($t->getId());
            $list = $avisRepo->findByTherapeute($t->getId());
            $nbAvis[$t->getId()]   = count($list);
            $avisList[$t->getId()] = array_slice($list, 0, 5);
        }

        $specialites = $repo->findDistinctSpecialites();

        return $this->render('client/therapeute_index.html.twig', [
            'therapeutes' => $therapeutes,
            'noteMoyenne' => $noteMoyenne,
            'nbAvis'      => $nbAvis,
            'avisList'    => $avisList,
            'specialites' => $specialites,
            'search'      => $search,
            'specialite'  => $specialite,
        ]);
    }

    // ============================================================
    // BACKOFFICE ADMIN
    // ============================================================

    #[Route('/admin/therapeutes', name: 'admin_therapeutes')]
    public function adminIndex(
        TherapeuteRepository $repo,
        AvisRepository $avisRepo,
        SessionInterface $session
    ): Response {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        $therapeutes = $repo->findAll();

        $noteMoyenne = [];
        foreach ($therapeutes as $t) {
            $noteMoyenne[$t->getId()] = $avisRepo->getNoteMoyenne($t->getId());
        }

        return $this->render('admin/therapeute.html.twig', [
            'therapeutes' => $therapeutes,
            'noteMoyenne' => $noteMoyenne,
            'errors'      => [],
        ]);
    }

    #[Route('/admin/therapeutes/new', name: 'admin_therapeute_new', methods: ['POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        TherapeuteRepository $repo,
        AvisRepository $avisRepo,
        SessionInterface $session,
        GoogleVisionService $vision
    ): Response {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        $nom        = trim($request->request->get('nom', ''));
        $prenom     = trim($request->request->get('prenom', ''));
        $email      = trim($request->request->get('email', ''));
        $telephone  = trim($request->request->get('telephone', ''));
        $specialite = trim($request->request->get('specialite', ''));

        $errors = [];

        if (empty($nom) || strlen($nom) < 2)
            $errors[] = 'Le nom doit avoir au moins 2 caractères';
        if (empty($prenom) || strlen($prenom) < 2)
            $errors[] = 'Le prénom doit avoir au moins 2 caractères';
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = 'L\'adresse email est invalide';
        if (!empty($email) && $repo->findOneBy(['email' => $email]))
            $errors[] = 'Cet email est déjà utilisé par un autre thérapeute';
        if (!empty($telephone) && !preg_match('/^\d{8,15}$/', $telephone))
            $errors[] = 'Le téléphone doit contenir entre 8 et 15 chiffres';
        if (empty($specialite))
            $errors[] = 'La spécialité est obligatoire';

        $imageFile = $request->files->get('image');
        $imagePath = null;
        if ($imageFile) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!in_array($imageFile->getMimeType(), $allowedMimes))
                $errors[] = 'La photo doit être au format JPG, PNG, WEBP ou GIF';
            elseif ($imageFile->getSize() > 3 * 1024 * 1024)
                $errors[] = 'La photo ne doit pas dépasser 3 Mo';
        }

        if (!empty($errors)) {
            $therapeutes = $repo->findAll();
            $noteMoyenne = [];
            foreach ($therapeutes as $t)
                $noteMoyenne[$t->getId()] = $avisRepo->getNoteMoyenne($t->getId());
            return $this->render('admin/therapeute.html.twig', [
                'therapeutes' => $therapeutes,
                'noteMoyenne' => $noteMoyenne,
                'errors'      => $errors,
                'form'        => compact('nom', 'prenom', 'email', 'telephone', 'specialite'),
            ]);
        }

        if ($imageFile) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/therapeutes/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileName  = uniqid('therapeute_') . '.' . $imageFile->guessExtension();
            $imageFile->move($uploadDir, $fileName);
            $imagePath = '/uploads/therapeutes/' . $fileName;
        }

        // Handle certificate file upload + Google Vision OCR
        $certPath  = null;
        $certTexte = null;
        $certStatut = null;
        $certFile = $request->files->get('certificat_file');
        if ($certFile) {
            $certDir = $this->getParameter('kernel.project_dir') . '/public/uploads/certificats/';
            if (!is_dir($certDir)) mkdir($certDir, 0777, true);
            $certName = uniqid('cert_') . '.' . $certFile->guessExtension();
            $certFile->move($certDir, $certName);
            $certPath   = '/uploads/certificats/' . $certName;
            $fullPath   = $this->getParameter('kernel.project_dir') . '/public' . $certPath;
            $certTexte  = $vision->extractTextFromFile($fullPath);
            $certStatut = $vision->isCertificatValid($certTexte) ? 'en_attente' : 'refuse';
        }

        $t = new Therapeute();
        $t->setNom($nom);
        $t->setPrenom($prenom);
        $t->setEmail($email ?: null);
        $t->setTelephone($telephone ?: null);
        $t->setSpecialite($specialite);
        $t->setImage($imagePath);
        $t->setCertificat($certPath);
        $t->setStatutCertificat($certStatut);
        $t->setCertificatTexte($certTexte);
        $t->setDateInscription(new \DateTime());
        $em->persist($t);
        $em->flush();

        $this->addFlash('success', 'Thérapeute ajouté avec succès');  // ✅

        return $this->redirectToRoute('admin_therapeutes');
    }

    #[Route('/admin/therapeutes/edit/{id}', name: 'admin_therapeute_edit', methods: ['POST'])]
    public function edit(
        Request $request,
        Therapeute $therapeute,
        EntityManagerInterface $em,
        SessionInterface $session,
        GoogleVisionService $vision
    ): Response {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        $nom       = trim($request->request->get('nom', ''));
        $prenom    = trim($request->request->get('prenom', ''));
        $email     = trim($request->request->get('email', ''));
        $telephone = trim($request->request->get('telephone', ''));

        $errors = [];
        if (empty($nom) || strlen($nom) < 2)       $errors[] = 'Le nom doit avoir au moins 2 caractères';
        if (empty($prenom) || strlen($prenom) < 2)  $errors[] = 'Le prénom doit avoir au moins 2 caractères';
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide';
        if (!empty($telephone) && !preg_match('/^\d{8,15}$/', $telephone)) $errors[] = 'Téléphone invalide';

        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!in_array($imageFile->getMimeType(), $allowedMimes))
                $errors[] = 'La photo doit être au format JPG, PNG, WEBP ou GIF';
            elseif ($imageFile->getSize() > 3 * 1024 * 1024)
                $errors[] = 'La photo ne doit pas dépasser 3 Mo';
        }

        if (!empty($errors))
            return $this->redirectToRoute('admin_therapeutes');

        if ($imageFile) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/therapeutes/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            if ($therapeute->getImage()) {
                $oldFile = $this->getParameter('kernel.project_dir') . '/public' . $therapeute->getImage();
                if (file_exists($oldFile)) @unlink($oldFile);
            }
            $fileName = uniqid('therapeute_') . '.' . $imageFile->guessExtension();
            $imageFile->move($uploadDir, $fileName);
            $therapeute->setImage('/uploads/therapeutes/' . $fileName);
        }

        $therapeute->setNom($nom);
        $therapeute->setPrenom($prenom);
        $therapeute->setEmail($email ?: null);
        $therapeute->setTelephone($telephone ?: null);
        $therapeute->setSpecialite($request->request->get('specialite'));

        $certFile = $request->files->get('certificat_file');
        if ($certFile) {
            $certDir = $this->getParameter('kernel.project_dir') . '/public/uploads/certificats/';
            if (!is_dir($certDir)) mkdir($certDir, 0777, true);
            $certName  = uniqid('cert_') . '.' . $certFile->guessExtension();
            $certFile->move($certDir, $certName);
            $certPath  = '/uploads/certificats/' . $certName;
            $fullPath  = $this->getParameter('kernel.project_dir') . '/public' . $certPath;
            $certTexte = $vision->extractTextFromFile($fullPath);
            $therapeute->setCertificat($certPath);
            $therapeute->setCertificatTexte($certTexte);
            $therapeute->setStatutCertificat($vision->isCertificatValid($certTexte) ? 'en_attente' : 'refuse');
        }

        $em->flush();

        $this->addFlash('success', 'Thérapeute modifié avec succès');  // ✅

        return $this->redirectToRoute('admin_therapeutes');
    }

    #[Route('/admin/therapeutes/{id}/certifier/{statut}', name: 'admin_therapeute_certifier')]
    public function certifier(
        Therapeute $therapeute,
        string $statut,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        if (in_array($statut, ['verifie', 'refuse'])) {
            $therapeute->setStatutCertificat($statut);
            $em->flush();
            $this->addFlash('success', 'Certificat ' . ($statut === 'verifie' ? 'vérifié ✅' : 'refusé ❌'));
        }
        return $this->redirectToRoute('admin_therapeutes');
    }

    #[Route('/admin/therapeutes/delete/{id}', name: 'admin_therapeute_delete')]
    public function delete(
        Therapeute $therapeute,
        EntityManagerInterface $em,
        SessionInterface $session
    ): Response {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');

        if ($therapeute->getImage()) {
            $file = $this->getParameter('kernel.project_dir') . '/public' . $therapeute->getImage();
            if (file_exists($file)) @unlink($file);
        }

        $em->remove($therapeute);
        $em->flush();

        $this->addFlash('success', 'Thérapeute supprimé avec succès');  // ✅

        return $this->redirectToRoute('admin_therapeutes');
    }
}