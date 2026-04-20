<?php
namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $projectDir
    ) {}

    public function sendConfirmationAbonnement(
        string $toEmail,
        string $toName,
        string $abonnementNom,
        float  $prix,
        int    $dureeMois,
        string $dateAchat,
        string $dateExpiration,
        string $numeroRecu,
        string $qrCodeBase64
    ): bool {
        $html = $this->buildEmailHtml(
            $toName, $abonnementNom, $prix,
            $dureeMois, $dateAchat, $dateExpiration,
            $numeroRecu, $qrCodeBase64
        );

        try {
            $email = (new Email())
                ->from(new Address('adminmindrow@gmail.com', 'MindGrow'))
                ->to(new Address($toEmail, $toName))
                ->subject('✅ Confirmation abonnement MindGrow — ' . $abonnementNom)
                ->html($html);

            $this->mailer->send($email);
            $this->saveEmailToFile($toEmail, $abonnementNom, $html, $numeroRecu, 'sent');
            return true;

        } catch (\Throwable $e) {
            // Log complet de l'erreur
            $this->saveEmailToFile($toEmail, $abonnementNom, $html, $numeroRecu, 'error');
            error_log('[MindGrow Email Error] ' . $e->getMessage());
            // Sauvegarder l'erreur pour debug
            $errorFile = $this->projectDir . '/var/emails/last_error.txt';
            file_put_contents($errorFile, date('Y-m-d H:i:s') . ' | ' . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        }
    }
public function sendReservationSeanceEmail(
    string $toEmail,
    string $nom,
    string $titre,
    string $date,
    string $lieu
): bool {

    $html = $this->buildReservationSeanceHtml($nom, $titre, $date, $lieu);

    try {
        $email = (new Email())
            ->from(new Address('adminmindrow@gmail.com', 'MindGrow'))
            ->to(new Address($toEmail))
            ->subject('🌿 Réservation séance confirmée - MindGrow')
            ->html($html);

        $this->mailer->send($email);
        return true;

    } catch (\Throwable $e) {
        error_log($e->getMessage());
        return false;
    }
}

private function buildReservationSeanceHtml(
    string $nom,
    string $titre,
    string $date,
    string $lieu
): string {

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body {
    font-family: Arial;
    background: #f4f7fb;
    margin: 0;
}
.container {
    max-width: 600px;
    margin: auto;
    background: white;
    border-radius: 12px;
    overflow: hidden;
}
.header {
    background: linear-gradient(135deg,#0b7a8f,#065f6e);
    color: white;
    text-align: center;
    padding: 25px;
}
.content {
    padding: 25px;
}
.box {
    background: #f0fdfa;
    padding: 12px;
    margin: 10px 0;
    border-left: 5px solid #0b7a8f;
    border-radius: 8px;
}
.btn {
    display: inline-block;
    background: #0b7a8f;
    color: white;
    padding: 12px 18px;
    border-radius: 8px;
    text-decoration: none;
    margin-top: 15px;
}
</style>
</head>
<body>

<div class="container">

<div class="header">
    <h2>🌿 MindGrow</h2>
    <p>Réservation confirmée</p>
</div>

<div class="content">

    <h3>Bonjour {$nom} 👋</h3>

    <p>Votre séance est confirmée 🎉</p>

    <div class="box">📌 Séance : {$titre}</div>
    <div class="box">📅 Date : {$date}</div>
    <div class="box">📍 Lieu : {$lieu}</div>

    <p>Merci pour votre confiance 💚</p>

    <a class="btn" href="http://localhost:8000/mes-reservations">
        Voir mes réservations
    </a>

</div>

</div>

</body>
</html>
HTML;
}
    /**
     * Sauvegarde l'email en HTML dans var/emails/ pour pouvoir le visualiser
     */
    private function saveEmailToFile(string $to, string $plan, string $html, string $numero, string $status = 'draft'): void
    {
        $dir = $this->projectDir . '/var/emails/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filename = $dir . $status . '_' . $numero . '_' . date('Y-m-d_H-i-s') . '.html';
        file_put_contents($filename, $html);
    }

    private function buildEmailHtml(
        string $nom, string $plan, float $prix,
        int $duree, string $dateAchat, string $dateExp,
        string $numero, string $qrB64
    ): string {
        $tva   = number_format($prix * 0.19, 2);
        $total = number_format($prix * 1.19, 2);
        $qrTag = $qrB64 ? '<img src="data:image/svg+xml;base64,' . $qrB64 . '" width="130" height="130" style="border-radius:8px;display:block;margin:0 auto;" />' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { background:#f0f4f8; font-family:'Segoe UI',Arial,sans-serif; color:#1e293b; }
  .wrapper { max-width:600px; margin:0 auto; padding:32px 16px; }
  .header { background:linear-gradient(135deg,#0b7a8f,#065f6e); border-radius:20px 20px 0 0; padding:40px 40px 32px; text-align:center; }
  .logo-circle { width:64px; height:64px; background:rgba(255,255,255,.15); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:28px; margin-bottom:16px; }
  .header h1 { color:white; font-size:26px; font-weight:800; }
  .header p  { color:rgba(255,255,255,.8); font-size:14px; margin-top:6px; }
  .badge-wrap { background:white; text-align:center; padding:18px; }
  .badge-success { display:inline-flex; align-items:center; gap:8px; background:#dcfce7; color:#166534; border-radius:30px; padding:8px 20px; font-size:14px; font-weight:700; }
  .body { background:white; padding:32px 40px; }
  .greeting { font-size:18px; font-weight:700; margin-bottom:6px; }
  .intro { color:#64748b; font-size:14px; line-height:1.6; margin-bottom:24px; }
  .plan-card { background:linear-gradient(135deg,#f0fdfa,#e0f2fe); border:1.5px solid #bae6fd; border-radius:16px; padding:18px 22px; margin-bottom:22px; display:flex; justify-content:space-between; align-items:center; }
  .plan-name { font-size:18px; font-weight:800; color:#0b7a8f; }
  .plan-duration { font-size:13px; color:#64748b; margin-top:2px; }
  .plan-price { font-size:30px; font-weight:800; color:#0b7a8f; text-align:right; }
  .section-title { font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.08em; margin:20px 0 10px; }
  table { width:100%; border-collapse:collapse; }
  td { padding:10px 0; font-size:14px; border-bottom:1px solid #f1f5f9; }
  td:first-child { color:#64748b; }
  td:last-child { font-weight:600; text-align:right; }
  .total-row td { border-top:2px solid #0b7a8f; border-bottom:none; padding-top:12px; }
  .total-row td:last-child { font-size:20px; color:#0b7a8f; font-weight:800; }
  .qr-section { text-align:center; background:#f8fafc; border-radius:16px; padding:22px; margin-top:22px; border:1.5px dashed #cbd5e1; }
  .qr-label { font-size:12px; color:#64748b; margin-top:10px; }
  .qr-number { font-size:13px; font-weight:700; color:#0b7a8f; margin-top:4px; font-family:monospace; letter-spacing:1px; }
  .cta-wrap { text-align:center; margin-top:26px; }
  .cta-btn { display:inline-block; background:linear-gradient(135deg,#0b7a8f,#065f6e); color:white !important; text-decoration:none; padding:14px 36px; border-radius:12px; font-size:15px; font-weight:700; }
  .footer { background:#f8fafc; border-radius:0 0 20px 20px; padding:22px 40px; text-align:center; color:#94a3b8; font-size:12px; border-top:1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="logo-circle">🌱</div>
    <h1>MindGrow</h1>
    <p>Plateforme de bien-être mental</p>
  </div>
  <div class="badge-wrap">
    <span class="badge-success">✓ Paiement confirmé — Abonnement actif</span>
  </div>
  <div class="body">
    <p class="greeting">Bonjour {$nom} 👋</p>
    <p class="intro">Merci pour votre confiance ! Votre abonnement <strong>{$plan}</strong> est maintenant actif.</p>
    <div class="plan-card">
      <div>
        <div class="plan-name">{$plan}</div>
        <div class="plan-duration">Durée : {$duree} mois</div>
      </div>
      <div class="plan-price">{$prix} <small style="font-size:14px">TND</small></div>
    </div>
    <div class="section-title">Détails de la transaction</div>
    <table>
      <tr><td>N° de reçu</td><td>{$numero}</td></tr>
      <tr><td>Date d'achat</td><td>{$dateAchat}</td></tr>
      <tr><td>Expiration</td><td>{$dateExp}</td></tr>
      <tr><td>Sous-total HT</td><td>{$prix} TND</td></tr>
      <tr><td>TVA (19%)</td><td>{$tva} TND</td></tr>
      <tr class="total-row"><td>Total TTC</td><td>{$total} TND</td></tr>
    </table>
    <div class="qr-section">
      {$qrTag}
      <div class="qr-label">QR Code de vérification de votre abonnement</div>
      <div class="qr-number">{$numero}</div>
    </div>
    <div class="cta-wrap">
      <a href="http://localhost:8000/abonnements" class="cta-btn">Accéder à mon espace →</a>
    </div>
  </div>
  <div class="footer">
    <strong>MindGrow</strong> — Votre bien-être mental est notre priorité<br>
    Email généré le {$dateAchat} — Contact : adminmindrow@gmail.com
  </div>
</div>
</body>
</html>
HTML;
    }
}
