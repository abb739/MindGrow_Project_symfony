<?php
namespace App\Service;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;

class QrCodeService
{
    public function generateBase64(string $data, int $size = 200): string
    {
        $qrCode = new QrCode(
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(11, 122, 143),
            backgroundColor: new Color(255, 255, 255),
        );

        $writer = new SvgWriter();
        return base64_encode($writer->write($qrCode)->getString());
    }

    public function generateSvg(string $data, int $size = 200): string
    {
        $qrCode = new QrCode(
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(11, 122, 143),
            backgroundColor: new Color(255, 255, 255),
        );

        $writer = new SvgWriter();
        return $writer->write($qrCode)->getString();
    }

    public function buildAbonnementQrData(string $numero, string $plan, string $dateExp): string
    {
        // Texte lisible et structuré — bien plus propre quand scanné
        return implode("\n", [
            'MINDGROW',
            '-------------------',
            'Reference : ' . $numero,
            'Plan      : ' . $plan,
            'Expiration: ' . $dateExp,
            '-------------------',
            'Abonnement Actif',
        ]);
    }
}
