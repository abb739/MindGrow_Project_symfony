<?php

namespace App\Tests\Unit\Service;

use App\Service\GeminiService;
use PHPUnit\Framework\TestCase;

class GeminiSentimentTest extends TestCase
{
    // ── analyserSentimentAvis fallback behaviour ─────────────────────────────

    public function testSentimentFallbackPositiveForHighNote(): void
    {
        $service = new GeminiService('INVALID_KEY');
        $result  = $service->analyserSentimentAvis('Excellent thérapeute, très professionnel.', 5);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('sentiment', $result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('resume', $result);
        $this->assertArrayHasKey('reponse_suggeree', $result);
        // Fallback: note 5 → positif
        $this->assertSame('positif', $result['sentiment']);
        $this->assertSame(1.0, $result['score']);
    }

    public function testSentimentFallbackNegativeForLowNote(): void
    {
        $service = new GeminiService('INVALID_KEY');
        $result  = $service->analyserSentimentAvis('Décevant, pas utile du tout.', 1);

        $this->assertSame('négatif', $result['sentiment']);
        $this->assertEqualsWithDelta(0.2, $result['score'], 0.01);
    }

    public function testSentimentFallbackNeutreForMidNote(): void
    {
        $service = new GeminiService('INVALID_KEY');
        $result  = $service->analyserSentimentAvis('Correct, rien d\'exceptionnel.', 3);

        $this->assertSame('neutre', $result['sentiment']);
    }

    public function testSentimentAlwaysReturnsString(): void
    {
        $service = new GeminiService('INVALID_KEY');
        $result  = $service->analyserSentimentAvis('', 3);

        $this->assertIsString($result['resume']);
        $this->assertIsString($result['reponse_suggeree']);
        $this->assertNotEmpty($result['resume']);
    }

    // ── chatAvecHistorique ───────────────────────────────────────────────────

    public function testChatAvecHistoriqueAppendsToHistory(): void
    {
        $service = new GeminiService('INVALID_KEY');
        $result  = $service->chatAvecHistorique('Bonjour', []);

        $this->assertArrayHasKey('reply', $result);
        $this->assertArrayHasKey('history', $result);
        $this->assertIsString($result['reply']);
        $this->assertCount(2, $result['history']); // user + assistant
        $this->assertSame('user',      $result['history'][0]['role']);
        $this->assertSame('assistant', $result['history'][1]['role']);
        $this->assertSame('Bonjour',   $result['history'][0]['text']);
    }

    public function testChatAvecHistoriquePreservesExistingHistory(): void
    {
        $service = new GeminiService('INVALID_KEY');
        $history = [
            ['role' => 'user',      'text' => 'Premier message'],
            ['role' => 'assistant', 'text' => 'Première réponse'],
        ];

        $result = $service->chatAvecHistorique('Deuxième message', $history);

        $this->assertCount(4, $result['history']);
        $this->assertSame('Premier message', $result['history'][0]['text']);
    }

    // ── analyserHumeurEtRecommander ──────────────────────────────────────────

    public function testAnalyserHumeurReturnsExpectedKeys(): void
    {
        $service = new GeminiService('INVALID_KEY');
        $result  = $service->analyserHumeurEtRecommander('Je me sens très stressé', []);

        $this->assertArrayHasKey('humeur', $result);
        $this->assertArrayHasKey('intensite', $result);
        $this->assertArrayHasKey('programmes_recommandes', $result);
        $this->assertArrayHasKey('message_empathique', $result);
        $this->assertIsArray($result['programmes_recommandes']);
        $this->assertIsString($result['message_empathique']);
    }

    public function testAnalyserHumeurFallbackValuesAreValid(): void
    {
        $service = new GeminiService('INVALID_KEY');
        $result  = $service->analyserHumeurEtRecommander('test', []);

        $validHumeurs = ['anxieux', 'triste', 'stressé', 'fatigué', 'heureux', 'en colère', 'neutre'];
        $this->assertContains($result['humeur'], $validHumeurs);

        $validIntensites = ['faible', 'modérée', 'élevée'];
        $this->assertContains($result['intensite'], $validIntensites);
    }
}
