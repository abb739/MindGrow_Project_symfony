<?php

namespace App\Tests\Unit\Service;

use App\Service\WordFilterService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class WordFilterServiceTest extends TestCase
{
    private WordFilterService $service;
    /** @var HttpClientInterface&MockObject */
    private HttpClientInterface $http;

    protected function setUp(): void
    {
        $this->http    = $this->createMock(HttpClientInterface::class);
        $this->service = new WordFilterService($this->http);
    }

    // ── containsBadWords — local list ────────────────────────────────────────

    public function testContainsBadWordsReturnsTrueForLocalFrenchWord(): void
    {
        // HTTP should NOT be called — local list short-circuits
        $this->http->expects($this->never())->method('request');

        $this->assertTrue($this->service->containsBadWords('c\'est vraiment de la merde'));
    }

    public function testContainsBadWordsReturnsTrueForLocalEnglishWord(): void
    {
        $this->http->expects($this->never())->method('request');
        $this->assertTrue($this->service->containsBadWords('what the fuck is this'));
    }

    public function testContainsBadWordsReturnsFalseForCleanText(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('false');

        $this->http->method('request')->willReturn($response);

        $this->assertFalse($this->service->containsBadWords('Bonjour, comment allez-vous ?'));
    }

    public function testContainsBadWordsReturnsFalseWhenApiThrows(): void
    {
        $this->http->method('request')->willThrowException(new \RuntimeException('timeout'));

        // No local bad word, API fails → must return false (safe default)
        $this->assertFalse($this->service->containsBadWords('Hello world'));
    }

    public function testContainsBadWordsIsCaseInsensitive(): void
    {
        $this->http->expects($this->never())->method('request');

        $this->assertTrue($this->service->containsBadWords('MERDE'));
        $this->assertTrue($this->service->containsBadWords('Fuck'));
    }

    public function testContainsBadWordsRequiresWholeWord(): void
    {
        // "scunthorpe problem" — partial matches should NOT trigger
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('false');
        $this->http->method('request')->willReturn($response);

        // "connard" is in the list but "connardise" (hypothetical) should not be triggered
        // Here we test that "pussy" inside "pussyness" won't match as whole word
        $this->assertFalse($this->service->containsBadWords('Je joue avec ma pussycat'));
    }

    public function testContainsBadWordsCallsApiForCleanText(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('true');

        $this->http->expects($this->once())
            ->method('request')
            ->with('GET', 'https://www.purgomalum.com/service/containsprofanity', $this->anything())
            ->willReturn($response);

        $result = $this->service->containsBadWords('some unknown offensive text');
        $this->assertTrue($result);
    }

    // ── filterText ───────────────────────────────────────────────────────────

    public function testFilterTextReplacesKnownBadWords(): void
    {
        $filtered = $this->service->filterText('Tu es un connard et une salope');
        $this->assertStringContainsString('***', $filtered);
        $this->assertStringNotContainsString('connard', $filtered);
        $this->assertStringNotContainsString('salope', $filtered);
    }

    public function testFilterTextPreservesCleanWords(): void
    {
        $text     = 'Bonjour, bienvenue sur MindGrow !';
        $filtered = $this->service->filterText($text);
        $this->assertSame($text, $filtered);
    }

    public function testFilterTextHandlesMultipleOccurrences(): void
    {
        $filtered = $this->service->filterText('shit shit shit');
        $this->assertStringNotContainsString('shit', $filtered);
        $this->assertSame(3, substr_count($filtered, '***'));
    }

    public function testFilterTextHandlesEmptyString(): void
    {
        $this->assertSame('', $this->service->filterText(''));
    }

    public function testFilterTextHandlesMixedCase(): void
    {
        $filtered = $this->service->filterText('MERDE tout va bien');
        $this->assertStringNotContainsString('MERDE', $filtered);
        $this->assertStringContainsString('***', $filtered);
    }
}
