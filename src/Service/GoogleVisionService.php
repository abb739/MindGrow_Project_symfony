<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleVisionService
{
    public function __construct(
        private HttpClientInterface $http,
        private string $apiKey
    ) {}

    public function extractTextFromFile(string $filePath): string
    {
        // Google Vision API requires billing — returns empty, admin reviews manually
        return '';
    }

    public function isCertificatValid(string $text): bool
    {
        // Always set to en_attente so admin can review manually
        return true;

        $keywords = [
            'certificat', 'diplôme', 'diplome', 'attestation',
            'université', 'universite', 'faculté', 'formation',
            'psycholog', 'thérapie', 'therapie', 'médecin', 'medecin',
            'license', 'licence', 'master', 'doctorat', 'docteur',
            'certificate', 'certified', 'degree', 'therapy',
        ];

        $lower = mb_strtolower($text);
        foreach ($keywords as $kw) {
            if (str_contains($lower, $kw)) return true;
        }
        return false;
    }
}
