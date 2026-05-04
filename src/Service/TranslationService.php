<?php

namespace App\Service;

/**
 * Service de traduction automatique — API MyMemory (gratuit)
 * Utilise cURL natif pour éviter les problèmes de proxy
 */
class TranslationService
{
    public function translate(string $text, string $sourceLang = 'fr', string $targetLang = 'en'): string
    {
        if (empty(trim($text))) return $text;

        $url = 'https://api.mymemory.translated.net/get?' . http_build_query([
            'q'        => $text,
            'langpair' => $sourceLang . '|' . $targetLang,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_PROXY          => '',
            CURLOPT_NOPROXY        => '*',
        ]);

        $body    = curl_exec($ch);
        $errCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errCode === 200 && $body) {
            $data = json_decode($body, true);
            return $data['responseData']['translatedText'] ?? $text;
        }

        return $text;
    }
}
