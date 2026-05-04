<?php

namespace App\Service;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Service de génération de PDF
 * Utilise dompdf/dompdf (déjà dans composer.json)
 * Génère : reçus d'abonnement, tickets de réservation
 */
class PdfService
{
    public function generateFromHtml(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    // ──────────────────────────────────────────────
    // REÇU ABONNEMENT
    // ──────────────────────────────────────────────
    public function generateRecuAbonnement(array $data, string $qrCodeBase64 = ''): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
        <style>
            body { font-family: DejaVu Sans, sans-serif; color: #1e293b; margin: 40px; }
            .header { text-align: center; border-bottom: 3px solid #0b7a8f; padding-bottom: 20px; margin-bottom: 30px; }
            .logo { font-size: 28px; font-weight: bold; color: #0b7a8f; letter-spacing: -1px; }
            .subtitle { color: #64748b; font-size: 13px; margin-top: 4px; }
            .badge { display: inline-block; background: #dcfce7; color: #166534; padding: 4px 16px;
                     border-radius: 20px; font-size: 12px; font-weight: bold; margin-top: 8px; }
            .section { margin-bottom: 24px; }
            .section-title { font-size: 14px; font-weight: bold; color: #0b7a8f; text-transform: uppercase;
                              letter-spacing: 1px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; margin-bottom: 12px; }
            table { width: 100%; border-collapse: collapse; }
            td { padding: 8px 4px; font-size: 13px; }
            td:first-child { color: #64748b; width: 45%; }
            td:last-child { font-weight: bold; }
            .total-row td { border-top: 2px solid #0b7a8f; padding-top: 12px; font-size: 16px; }
            .total-row td:last-child { color: #0b7a8f; font-size: 18px; }
            .footer { text-align: center; margin-top: 40px; color: #94a3b8; font-size: 11px;
                      border-top: 1px solid #e2e8f0; padding-top: 16px; }
            .qr-placeholder { text-align: center; margin: 20px 0; color: #94a3b8; font-size: 11px; }
        </style></head><body>
        <div class="header">
            <div class="logo">🌱 MindGrow</div>
            <div class="subtitle">Plateforme de bien-être mental</div>
            <div class="badge">✓ Paiement confirmé</div>
        </div>

        <div class="section">
            <div class="section-title">Reçu d\'abonnement</div>
            <table>
                <tr><td>Numéro de reçu</td><td>#' . htmlspecialchars($data['numero'] ?? uniqid('REC-')) . '</td></tr>
                <tr><td>Date d\'achat</td><td>' . htmlspecialchars($data['date'] ?? date('d/m/Y')) . '</td></tr>
                <tr><td>Client</td><td>' . htmlspecialchars($data['client'] ?? '') . '</td></tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Détails de l\'abonnement</div>
            <table>
                <tr><td>Nom de l\'abonnement</td><td>' . htmlspecialchars($data['abonnement'] ?? '') . '</td></tr>
                <tr><td>Durée</td><td>' . htmlspecialchars($data['duree'] ?? '') . ' mois</td></tr>
                <tr><td>Date de début</td><td>' . htmlspecialchars($data['dateDebut'] ?? '') . '</td></tr>
                <tr><td>Date d\'expiration</td><td>' . htmlspecialchars($data['dateExpiration'] ?? '') . '</td></tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Montant</div>
            <table>
                <tr><td>Sous-total</td><td>' . htmlspecialchars($data['prix'] ?? '0') . ' TND</td></tr>
                <tr><td>TVA (19%)</td><td>' . number_format(($data['prix'] ?? 0) * 0.19, 2) . ' TND</td></tr>
                <tr class="total-row"><td>TOTAL</td><td>' . number_format(($data['prix'] ?? 0) * 1.19, 2) . ' TND</td></tr>
            </table>
        </div>

        ' . ($qrCodeBase64 ? '
        <div style="text-align:center;margin:20px 0;padding:16px;background:#f8fafc;border-radius:12px;border:1.5px dashed #cbd5e1;">
            <div style="font-size:11px;color:#64748b;margin-bottom:8px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;">QR Code de vérification</div>
            <img src="data:image/svg+xml;base64,' . $qrCodeBase64 . '" width="110" height="110" style="border-radius:8px;display:block;margin:0 auto;" />
            <div style="font-size:11px;color:#94a3b8;margin-top:8px;">Scannez pour vérifier l\'authenticité</div>
        </div>
        ' : '') . '
        <div class="footer">
            MindGrow — Votre bien-être mental est notre priorité<br>
            Ce reçu a été généré automatiquement le ' . date('d/m/Y à H:i') . '
        </div>
        </body></html>';

        return $this->generateFromHtml($html);
    }
public function getQrCodeBase64(string $data): string
{
    $qrCode = new QrCode(
        data: $data,
        encoding: new Encoding('UTF-8'),
        errorCorrectionLevel: ErrorCorrectionLevel::High,
        size: 200,
        margin: 10
    );

    $writer = new SvgWriter();
    $result = $writer->write($qrCode);

    return base64_encode($result->getString());
}
    // ──────────────────────────────────────────────
    // TICKET RÉSERVATION SÉANCE
    // ──────────────────────────────────────────────
public function generateTicketReservation(array $data, string $qrCodeBase64 = ''): string
{
    $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<style>
    body {
        font-family: DejaVu Sans, sans-serif;
        color: #1e293b;
        margin: 40px;
    }

    .header {
        text-align: center;
        border-bottom: 3px solid #0b7a8f;
        padding-bottom: 20px;
        margin-bottom: 30px;
    }

    .logo {
        font-size: 28px;
        font-weight: bold;
        color: #0b7a8f;
    }

    .ticket-title {
        font-size: 20px;
        color: #065f6e;
        font-weight: bold;
        margin-top: 10px;
    }

    .badge {
        display: inline-block;
        background: #dbeafe;
        color: #1e40af;
        padding: 4px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        margin-top: 8px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    td {
        padding: 10px 6px;
        font-size: 13px;
        border-bottom: 1px solid #f1f5f9;
    }

    td:first-child {
        color: #64748b;
        width: 40%;
    }

    td:last-child {
        font-weight: bold;
    }

    .qr-section {
        text-align: center;
        margin-top: 30px;
        padding: 15px;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
    }

    .qr-title {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 10px;
        font-weight: bold;
    }

    .footer {
        text-align: center;
        margin-top: 40px;
        color: #94a3b8;
        font-size: 11px;
        border-top: 1px solid #e2e8f0;
        padding-top: 16px;
    }
</style>

</head>

<body>

<div class="header">
    <div class="logo">🌱 MindGrow</div>
    <div class="ticket-title">Ticket de Réservation</div>
    <div class="badge">✓ Réservation confirmée</div>
</div>

<table>
    <tr>
        <td>N° de réservation</td>
        <td>#' . htmlspecialchars($data['numero'] ?? uniqid('RES-')) . '</td>
    </tr>

    <tr>
        <td>Client</td>
        <td>' . htmlspecialchars($data['client'] ?? '') . '</td>
    </tr>

    <tr>
        <td>Séance</td>
        <td>' . htmlspecialchars($data['seance'] ?? '') . '</td>
    </tr>

    <tr>
        <td>Date</td>
        <td>' . htmlspecialchars($data['date'] ?? '') . '</td>
    </tr>

    <tr>
        <td>Lieu</td>
        <td>' . htmlspecialchars($data['lieu'] ?? '') . '</td>
    </tr>

    <tr>
        <td>Thérapeute</td>
        <td>' . htmlspecialchars($data['therapeute'] ?? 'Non assigné') . '</td>
    </tr>

    <tr>
        <td>Date de réservation</td>
        <td>' . date('d/m/Y à H:i') . '</td>
    </tr>
</table>

' . ($qrCodeBase64 ? '
<div style="text-align:center;margin-top:25px;">
    <div style="font-size:12px;margin-bottom:10px;color:#64748b;">
        QR Code de vérification
    </div>

    <img src="data:image/svg+xml;base64,' . trim($qrCodeBase64) . '" 
         style="width:120px;height:120px;display:block;margin:0 auto;" />

    <div style="font-size:10px;color:#94a3b8;margin-top:8px;">
        Scannez pour vérifier la réservation
    </div>
</div>
' : '') . '


<div class="footer">
    MindGrow — Présentez ce ticket lors de votre séance<br>
    Généré le ' . date('d/m/Y à H:i') . '
</div>

</body>
</html>';

    return $this->generateFromHtml($html);
}
}
