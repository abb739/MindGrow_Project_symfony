<?php
namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(SessionInterface $session): Response
    {
        if ($session->get('user_id')) {
            if ($session->get('user_role') === 'admin') {
                return $this->redirectToRoute('admin_dashboard');
            }
            return $this->redirectToRoute('client_dashboard');
        }
        return $this->redirectToRoute('login');
    }

    #[Route('/login', name: 'login')]
    public function login(Request $request, UtilisateurRepository $repo, SessionInterface $session): Response
    {
        if ($session->get('user_id')) {
            return $this->redirectToRoute('home');
        }

        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');

            // ✅ CONTRÔLE DE SAISIE PHP
            if (empty($email) || empty($password)) {
                return $this->render('auth/login.html.twig', ['error' => 'Email et mot de passe obligatoires']);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->render('auth/login.html.twig', ['error' => 'Format email invalide']);
            }

            $user = $repo->findOneBy(['email' => $email]);

            // 🔐 Vérification mot de passe (compatible texte clair + bcrypt)
            $valid = false;
            if ($user) {
                if (password_get_info($user->getMotDePasse())['algo'] !== null && password_get_info($user->getMotDePasse())['algo'] !== 0) {
                    // mot de passe hashé bcrypt
                    $valid = password_verify($password, $user->getMotDePasse());
                } else {
                    // mot de passe en clair (anciens comptes)
                    $valid = $user->getMotDePasse() === $password;
                }
            }

            if (!$valid) {
                return $this->render('auth/login.html.twig', ['error' => 'Email ou mot de passe incorrect']);
            }

            $session->set('user_id', $user->getId());
            $session->set('user_nom', $user->getNom() . ' ' . $user->getPrenom());
            $session->set('user_role', $user->getRole());

            if ($user->getRole() === 'admin') {
                return $this->redirectToRoute('admin_dashboard');
            }
            return $this->redirectToRoute('client_dashboard');
        }

        return $this->render('auth/login.html.twig', ['error' => null]);
    }

    #[Route('/register', name: 'register')]
    public function register(Request $request, EntityManagerInterface $em, UtilisateurRepository $repo): Response
    {
        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            $prenom = trim($request->request->get('prenom', ''));
            $email = trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');
            $confirm = $request->request->get('confirm', '');
            $role = $request->request->get('role', '');

            // ✅ CONTRÔLE DE SAISIE PHP complet
            $errors = [];

            if (empty($nom) || strlen($nom) < 2) {
                $errors[] = 'Le nom doit avoir au moins 2 caractères';
            }
            if (empty($prenom) || strlen($prenom) < 2) {
                $errors[] = 'Le prénom doit avoir au moins 2 caractères';
            }
            if (empty($email)) {
                $errors[] = 'L\'email est obligatoire';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Format email invalide';
            } elseif ($repo->findOneBy(['email' => $email])) {
                $errors[] = 'Cet email est déjà utilisé';
            }
            if (empty($password)) {
                $errors[] = 'Le mot de passe est obligatoire';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Le mot de passe doit avoir au moins 6 caractères';
            }
            if (!in_array($role, ['client', 'admin'])) {
                $errors[] = 'Veuillez choisir un rôle (Client ou Admin)';
            }
            if ($password !== $confirm) {
                $errors[] = 'Les mots de passe ne correspondent pas';
            }

            if (!empty($errors)) {
                return $this->render('auth/register.html.twig', [
                    'errors' => $errors,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'role' => $role,
                ]);
            }

            $user = new Utilisateur();
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            // 🔐 Hashage bcrypt du mot de passe
            $user->setMotDePasse(password_hash($password, PASSWORD_BCRYPT));
            $user->setRole($role);
            $user->setDateInscription(new \DateTime());
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('login');
        }

        return $this->render('auth/register.html.twig', ['errors' => [], 'nom' => '', 'prenom' => '', 'email' => '']);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->clear();
        return $this->redirectToRoute('login');
    }

    #[Route('/client', name: 'client_dashboard')]
    public function clientDashboard(SessionInterface $session): Response
    {
        if (!$session->get('user_id')) return $this->redirectToRoute('login');
        return $this->render('auth/client_dashboard.html.twig');
    }

    #[Route('/admin', name: 'admin_dashboard')]
    public function adminDashboard(SessionInterface $session): Response
    {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        return $this->render('auth/admin_dashboard.html.twig');
    }
}