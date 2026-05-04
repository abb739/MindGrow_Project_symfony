<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ApiAuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request, UtilisateurRepository $repo, SessionInterface $session): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid JSON'], 400);
        }

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            return new JsonResponse(['status' => 'error', 'message' => 'Email and password are required'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid email format'], 400);
        }

        $user = $repo->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid credentials'], 401);
        }

        $valid = password_verify($password, $user->getMotDePasse());

        if (!$valid) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid credentials'], 401);
        }

        $session->set('user_id', $user->getId());
        $session->set('user_nom', $user->getNom() . ' ' . $user->getPrenom());
        $session->set('user_role', $user->getRole());

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'role' => $user->getRole(),
            ]
        ]);
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em, UtilisateurRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid JSON'], 400);
        }

        $nom = trim($data['nom'] ?? '');
        $prenom = trim($data['prenom'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? '';

        $errors = [];

        if (empty($nom) || strlen($nom) < 2) {
            $errors[] = 'Nom must be at least 2 characters';
        }
        if (empty($prenom) || strlen($prenom) < 2) {
            $errors[] = 'Prenom must be at least 2 characters';
        }
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif ($repo->findOneBy(['email' => $email])) {
            $errors[] = 'Email already in use';
        }
        if (empty($password) || strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        if ($role !== 'client') {
            $errors[] = 'Only client role is allowed on registration';
        }

        if (!empty($errors)) {
            return new JsonResponse(['status' => 'error', 'errors' => $errors], 400);
        }

        $user = new Utilisateur();
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setEmail($email);
        $user->setMotDePasse(password_hash($password, PASSWORD_BCRYPT));
        $user->setRole($role);
        $user->setDateInscription(new \DateTime());

        $em->persist($user);
        $em->flush();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Registration successful',
            'user_id' => $user->getId()
        ], 201);
    }

    #[Route('/api/user/me', name: 'api_user_me', methods: ['GET'])]
    public function me(SessionInterface $session, UtilisateurRepository $repo): JsonResponse
    {
        $userId = $session->get('user_id');

        if (!$userId) {
            return new JsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $user = $repo->find($userId);

        if (!$user) {
            $session->clear();
            return new JsonResponse(['status' => 'error', 'message' => 'User not found'], 401);
        }

        return new JsonResponse([
            'status' => 'success',
            'user' => [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
            ]
        ]);
    }
}