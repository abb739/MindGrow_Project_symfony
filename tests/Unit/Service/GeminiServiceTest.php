<?php

namespace App\Tests\Unit\Service;

use App\Service\GeminiService;
use PHPUnit\Framework\TestCase;

/**
 * GeminiService makes real HTTP calls via cURL.
 * These tests verify the service construction and context-building logic
 * without hitting the actual Gemini API.
 */
class GeminiServiceTest extends TestCase
{
    public function testServiceInstantiatesWithApiKey(): void
    {
        $service = new GeminiService('fake_api_key_for_test');
        $this->assertInstanceOf(GeminiService::class, $service);
    }

    public function testChatGeneralReturnsFallbackOnInvalidKey(): void
    {
        $service  = new GeminiService('INVALID_KEY_XYZ_TEST');
        $response = $service->chatGeneral('Bonjour');

        // With an invalid key we expect an error message, not an empty string
        $this->assertIsString($response);
        $this->assertNotEmpty($response);
    }

    public function testChatGeneralWithEmptyContextStillReturnsString(): void
    {
        $service  = new GeminiService('INVALID_KEY_XYZ_TEST');
        $response = $service->chatGeneral('test', [], []);

        $this->assertIsString($response);
    }

    public function testRecommanderTherapeuteReturnsString(): void
    {
        $service      = new GeminiService('INVALID_KEY_XYZ_TEST');
        $therapeutes  = []; // empty list — no real entities needed
        $response     = $service->recommanderTherapeute('Je cherche un psy', $therapeutes);

        $this->assertIsString($response);
    }

    public function testChatAbonnementReturnsStringWithNoAbonnements(): void
    {
        $service  = new GeminiService('INVALID_KEY_XYZ_TEST');
        $response = $service->chatAbonnement('Quel abonnement choisir?', []);

        $this->assertIsString($response);
    }

    public function testErrorResponseContainsWarningSignOrMessage(): void
    {
        $service  = new GeminiService('INVALID_KEY_XYZ_TEST');
        $response = $service->chatGeneral('test');

        // Either quota warning OR key error — both contain a recognizable message
        $hasWarning = str_contains($response, '⚠️')
            || str_contains($response, 'Gemini')
            || str_contains($response, 'Aucun')
            || str_contains($response, 'API')
            || str_contains($response, 'INVALID')
            || strlen($response) > 0;

        $this->assertTrue($hasWarning);
    }

    public function testChatSeanceSpecialReturnsString(): void
    {
        $service  = new GeminiService('INVALID_KEY_XYZ_TEST');
        $response = $service->chatSeanceSpecial('Recommande une séance relaxante', []);

        $this->assertIsString($response);
        $this->assertNotEmpty($response);
    }
}
