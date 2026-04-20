<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer
    ) {}

    public function sendSeanceReminder(string $to, string $titre, string $date, string $lieu): void
    {
        $email = (new Email())
            ->from('mindgrow@gmail.com')
            ->to($to)
            ->subject('🔔 Rappel de votre séance MindGrow')
            ->html("
                <h2>🌿 Rappel de séance</h2>
                <p>Votre séance approche :</p>
                <ul>
                    <li><b>Titre :</b> $titre</li>
                    <li><b>Date :</b> $date</li>
                    <li><b>Lieu :</b> $lieu</li>
                </ul>
                <p>✨ À très bientôt sur MindGrow</p>
            ");

        $this->mailer->send($email);
    }
private function buildReservationEmail(string $titre, string $date, string $lieu): string
{
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f7fb;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 600px;
        margin: 30px auto;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .header {
        background: linear-gradient(135deg, #0b7a8f, #065f6e);
        padding: 25px;
        text-align: center;
        color: white;
    }
    .header h1 {
        margin: 0;
        font-size: 24px;
    }
    .content {
        padding: 25px;
        color: #333;
    }
    .box {
        background: #f0fdfa;
        border-left: 5px solid #0b7a8f;
        padding: 15px;
        margin: 10px 0;
        border-radius: 8px;
    }
    .label {
        font-weight: bold;
        color: #0b7a8f;
    }
    .footer {
        text-align: center;
        padding: 15px;
        font-size: 12px;
        color: #888;
        background: #f9fafb;
    }
    .btn {
        display: inline-block;
        margin-top: 20px;
        padding: 12px 20px;
        background: #0b7a8f;
        color: white;
        text-decoration: none;
        border-radius: 8px;
    }
</style>
</head>
<body>

<div class="container">

    <div class="header">
        <h1>🌿 MindGrow</h1>
        <p>Votre réservation est confirmée</p>
    </div>

    <div class="content">
        <h2>Bonjour 👋</h2>
        <p>Votre réservation a été enregistrée avec succès.</p>

        <div class="box">
            <p><span class="label">📌 Séance :</span> $titre</p>
        </div>

        <div class="box">
            <p><span class="label">📅 Date :</span> $date</p>
        </div>

        <div class="box">
            <p><span class="label">📍 Lieu :</span> $lieu</p>
        </div>

        <p>✨ Merci pour votre confiance et bienvenue chez MindGrow.</p>

        <a class="btn" href="http://localhost:8000/mes-reservations">
            Voir mes réservations
        </a>
    </div>

    <div class="footer">
        MindGrow © - Bien-être mental & développement personnel
    </div>

</div>

</body>
</html>
HTML;
}
public function sendReservationConfirmation(
    string $to,
    string $titre,
    string $date,
    string $lieu
): void {
    $email = (new \Symfony\Component\Mime\Email())
        ->from(new \Symfony\Component\Mime\Address('adminmindrow@gmail.com', 'MindGrow'))
        ->to($to)
        ->subject('🌿 Réservation confirmée - MindGrow')
        ->html($this->buildReservationEmail($titre, $date, $lieu));

    $this->mailer->send($email);
}
}