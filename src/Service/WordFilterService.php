<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WordFilterService
{
    private const BAD_WORDS = [
        // Français
        'merde', 'putain', 'salope', 'connard', 'con', 'enculé', 'bite',
        'couille', 'bordel', 'pute', 'salaud', 'abruti', 'débile',
        'imbécile', 'idiot', 'cretin', 'connasse', 'foutre',
        // Anglais
        'fuck', 'shit', 'asshole', 'bitch', 'bastard', 'dick',
        'pussy', 'cunt', 'faggot', 'retard', 'slut', 'whore',
    ];

    public function __construct(private HttpClientInterface $http) {}

    public function containsBadWords(string $text): bool
    {
        // Check local French/English list first
        if ($this->checkLocalList($text)) {
            return true;
        }

        // Check via PurgoMalum API (free, no key required)
        try {
            $response = $this->http->request('GET', 'https://www.purgomalum.com/service/containsprofanity', [
                'query'   => ['text' => $text],
                'timeout' => 3,
            ]);
            return $response->getContent() === 'true';
        } catch (\Throwable) {
            return false;
        }
    }

    public function filterText(string $text): string
    {
        $result = $text;
        foreach (self::BAD_WORDS as $bad) {
            $result = preg_replace('/\b' . preg_quote($bad, '/') . '\b/iu', '***', $result);
        }
        return $result;
    }

    private function checkLocalList(string $text): bool
    {
        $normalized = mb_strtolower($text);
        $normalized = preg_replace('/[^a-zA-Z\s\xC0-\xFF]/u', ' ', $normalized);
        $words      = preg_split('/\s+/', trim($normalized));

        foreach ($words as $word) {
            if (in_array($word, self::BAD_WORDS, true)) {
                return true;
            }
        }
        return false;
    }
}
