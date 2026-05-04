<?php
namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Repository\TherapeuteRepository;
use App\Repository\SeanceRepository;
use App\Repository\AchatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
    public function login(Request $request, UtilisateurRepository $repo, SessionInterface $session, HttpClientInterface $client): Response
    {
        if ($session->get('user_id')) {
            return $this->redirectToRoute('home');
        }

        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');
            $recaptchaToken = $request->request->get('g-recaptcha-response', '');

            // ✅ CONTRÔLE DE SAISIE PHP
            if (empty($email) || empty($password)) {
                return $this->render('client/login.html.twig', [
                    'error' => 'Email et mot de passe obligatoires',
                    'recaptcha_site_key' => $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'] ?? '',
                ]);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->render('client/login.html.twig', [
                    'error' => 'Format email invalide',
                    'recaptcha_site_key' => $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'] ?? '',
                ]);
            }
            if (!$this->verifyRecaptcha($recaptchaToken, $client)) {
                return $this->render('client/login.html.twig', [
                    'error' => 'Veuillez cocher « Je ne suis pas un robot ».',
                    'recaptcha_site_key' => $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'] ?? '',
                ]);
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
                return $this->render('client/login.html.twig', [
                    'error' => 'Email ou mot de passe incorrect',
                    'recaptcha_site_key' => $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'] ?? '',
                ]);
            }

            if (!$user->isVerified()) {
                return $this->render('client/login.html.twig', [
                    'error' => 'Veuillez vérifier votre compte par email avant de vous connecter.',
                    'recaptcha_site_key' => $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'] ?? '',
                ]);
            }

            $session->set('user_id', $user->getId());
            $session->set('user_nom', $user->getNom() . ' ' . $user->getPrenom());
            $session->set('user_role', $user->getRole());

            if ($user->getRole() === 'admin') {
                return $this->redirectToRoute('admin_dashboard');
            }
            return $this->redirectToRoute('client_dashboard');
        }

        return $this->render('client/login.html.twig', [
            'error' => null,
            'recaptcha_site_key' => $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'] ?? '',
        ]);
    }

    #[Route('/register', name: 'register')]
    public function register(Request $request, EntityManagerInterface $em, UtilisateurRepository $repo, MailerInterface $mailer, UrlGeneratorInterface $urlGenerator, HttpClientInterface $client): Response
    {
        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            $prenom = trim($request->request->get('prenom', ''));
            $email = trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');
            $confirm = $request->request->get('confirm', '');
            $role = $request->request->get('role', 'client');
            $recaptchaToken = $request->request->get('g-recaptcha-response', '');

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
            if ($role !== 'client') {
                $errors[] = 'Seul le rôle Client est autorisé lors de l\'inscription';
            }
            if ($password !== $confirm) {
                $errors[] = 'Les mots de passe ne correspondent pas';
            }
            if (!$this->verifyRecaptcha($recaptchaToken, $client)) {
                $errors[] = 'Veuillez cocher « Je ne suis pas un robot ».';
            }

            if (!empty($errors)) {
                return $this->render('client/register.html.twig', [
                    'errors' => $errors,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'role' => $role,
                    'recaptcha_site_key' => $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'] ?? '',
                ]);
            }

            $user = new Utilisateur();
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setMotDePasse(password_hash($password, PASSWORD_BCRYPT));
            $user->setRole('client');
            $user->setIsVerified(false);
            $user->setVerificationToken(bin2hex(random_bytes(32)));
            $user->setDateInscription(new \DateTime());
            $em->persist($user);
            $em->flush();

            $verificationUrl = $urlGenerator->generate('verify_email', ['token' => $user->getVerificationToken()], UrlGeneratorInterface::ABSOLUTE_URL);
            $message = (new Email())
                ->from('noreply@yourdomain.com')
                ->to($user->getEmail())
                ->subject('Vérifiez votre email')
                ->html(sprintf(
                    '<p>Bonjour %s,</p><p>Merci de vous être inscrit. Cliquez sur le lien ci-dessous pour vérifier votre adresse email :</p><p><a href="%s">%s</a></p>',
                    htmlspecialchars($user->getNom(), ENT_QUOTES, 'UTF-8'),
                    $verificationUrl,
                    $verificationUrl
                ));
            $mailer->send($message);

            $this->addFlash('success', 'Un email de vérification a été envoyé. Vérifiez votre boîte de réception.');
            return $this->redirectToRoute('login');
        }

        return $this->render('client/register.html.twig', [
            'errors' => [],
            'nom' => '',
            'prenom' => '',
            'email' => '',
            'recaptcha_site_key' => $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'] ?? '',
        ]);
    }

    #[Route('/forgot-password', name: 'forgot_password')]
    public function forgotPassword(Request $request, UtilisateurRepository $repo, EntityManagerInterface $em, MailerInterface $mailer, UrlGeneratorInterface $urlGenerator): Response
    {
        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));

            if ($email) {
                $user = $repo->findOneBy(['email' => $email]);
                if ($user) {
                    $resetToken = bin2hex(random_bytes(32));
                    $user->setResetToken($resetToken);
                    $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));
                    $em->flush();

                    $resetUrl = $urlGenerator->generate('reset_password', ['token' => $resetToken], UrlGeneratorInterface::ABSOLUTE_URL);
                    $message = (new Email())
                        ->from('noreply@yourdomain.com')
                        ->to($user->getEmail())
                        ->subject('Réinitialisation de votre mot de passe')
                        ->html(sprintf(
                            '<p>Bonjour %s,</p><p>Une demande de réinitialisation de mot de passe a été effectuée pour votre compte. Cliquez sur le lien ci-dessous pour définir un nouveau mot de passe :</p><p><a href="%s">%s</a></p><p>Ce lien expire dans 1 heure.</p>',
                            htmlspecialchars($user->getNom(), ENT_QUOTES, 'UTF-8'),
                            $resetUrl,
                            $resetUrl
                        ));
                    $mailer->send($message);
                }
            }

            $this->addFlash('success', 'Si un compte existe avec cette adresse, un lien de réinitialisation a été envoyé.');
            return $this->redirectToRoute('login');
        }

        return $this->render('client/forgot_password.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'reset_password')]
    public function resetPassword(string $token, Request $request, UtilisateurRepository $repo, EntityManagerInterface $em): Response
    {
        $user = $repo->findOneBy(['resetToken' => $token]);
        $now = new \DateTime();

        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < $now) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou expiré.');
            return $this->redirectToRoute('login');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password', '');
            $confirmPassword = $request->request->get('confirm', '');
            $errors = [];

            if (empty($newPassword)) {
                $errors[] = 'Le nouveau mot de passe est obligatoire.';
            } elseif (strlen($newPassword) < 6) {
                $errors[] = 'Le mot de passe doit comporter au moins 6 caractères.';
            }
            if ($newPassword !== $confirmPassword) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }

            if (empty($errors)) {
                $user->setMotDePasse(password_hash($newPassword, PASSWORD_BCRYPT));
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                $em->flush();

                $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('login');
            }

            return $this->render('client/reset_password.html.twig', [
                'errors' => $errors,
                'token' => $token,
            ]);
        }

        return $this->render('client/reset_password.html.twig', [
            'errors' => [],
            'token' => $token,
        ]);
    }

    private function verifyRecaptcha(string $token, HttpClientInterface $client): bool
    {
        $secret = $_ENV['GOOGLE_RECAPTCHA_SECRET'] ?? '';
        if (empty($secret) || empty($token)) {
            return false;
        }

        try {
            $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $secret,
                    'response' => $token,
                ],
            ]);
            $data = $response->toArray(false);
            return isset($data['success']) && $data['success'] === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    #[Route('/verify-email/{token}', name: 'verify_email')]
    public function verifyEmail(string $token, UtilisateurRepository $repo, EntityManagerInterface $em): Response
    {
        $user = $repo->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Lien de vérification invalide ou expiré.');
            return $this->redirectToRoute('login');
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $em->flush();

        $this->addFlash('success', 'Votre adresse e-mail a été vérifiée. Vous pouvez maintenant vous connecter.');
        return $this->redirectToRoute('login');
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
        return $this->render('client/dashboard.html.twig');
    }

    #[Route('/admin', name: 'admin_dashboard')]
    public function adminDashboard(
        SessionInterface $session,
        UtilisateurRepository $userRepo,
        TherapeuteRepository $therapeuteRepo,
        SeanceRepository $seanceRepo,
        AchatRepository $achatRepo
    ): Response {
        if ($session->get('user_role') !== 'admin') return $this->redirectToRoute('login');
        return $this->render('admin/dashboard.html.twig', [
            'totalUtilisateurs' => count($userRepo->findAll()),
            'totalTherapeutes'  => count($therapeuteRepo->findAll()),
            'totalSeances'      => count($seanceRepo->findAll()),
            'totalAbonnements'  => count($achatRepo->findAll()),
        ]);
    }
}