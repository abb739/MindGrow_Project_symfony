<?php

namespace App\Tests\Unit\Service;

use App\Service\CurrencyService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CurrencyServiceTest extends TestCase
{
    private CurrencyService $service;
    /** @var CacheInterface&MockObject */
    private CacheInterface $cache;
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->cache  = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new CurrencyService($this->cache, $this->logger);
    }

    // ── getTaux uses fallback when cache throws ─────────────────────────────

    public function testGetTauxReturnsFallbackWhenCacheThrows(): void
    {
        $this->cache
            ->method('get')
            ->willThrowException(new \RuntimeException('Cache down'));

        $this->logger->expects($this->once())->method('warning');

        $result = $this->service->getTaux();

        $this->assertSame('fallback', $result['source']);
        $this->assertArrayHasKey('rates', $result);
        $this->assertSame(1.0, $result['rates']['TND']);
    }

    public function testGetTauxReturnsCachedRates(): void
    {
        $fakeRates = [
            'rates'      => ['TND' => 1.0, 'EUR' => 0.30, 'USD' => 0.32],
            'source'     => 'live',
            'updated_at' => '2026-04-27T00:00:00Z',
        ];

        $this->cache
            ->method('get')
            ->willReturnCallback(function (string $key, callable $callback) use ($fakeRates) {
                return $fakeRates; // return cached value directly
            });

        $result = $this->service->getTaux();

        $this->assertSame('live', $result['source']);
        $this->assertSame(1.0, $result['rates']['TND']);
    }

    // ── convertir ───────────────────────────────────────────────────────────

    public function testConvertirTndReturnsSameAmount(): void
    {
        $this->cache->method('get')->willReturnCallback(
            fn(string $key, callable $cb) => $this->buildFakeCache()
        );

        $result = $this->service->convertir(100.0, 'TND');
        $this->assertSame(100.0, $result);
    }

    public function testConvertirEurUsesRate(): void
    {
        $this->cache->method('get')->willReturnCallback(
            fn(string $key, callable $cb) => $this->buildFakeCache()
        );

        // Fallback EUR rate = 0.2965, so 100 * 0.2965 = 29.65
        $result = $this->service->convertir(100.0, 'EUR');
        $this->assertEqualsWithDelta(29.65, $result, 0.01);
    }

    public function testConvertirIsCaseInsensitive(): void
    {
        $this->cache->method('get')->willThrowException(new \RuntimeException());
        $this->logger->method('warning');

        $lower = $this->service->convertir(100.0, 'eur');
        $upper = $this->service->convertir(100.0, 'EUR');

        $this->assertSame($lower, $upper);
    }

    public function testConvertirMultiple(): void
    {
        $this->cache->method('get')->willThrowException(new \RuntimeException());
        $this->logger->method('warning');

        $result = $this->service->convertirMultiple(100.0, ['EUR', 'USD', 'TND']);

        $this->assertArrayHasKey('EUR', $result);
        $this->assertArrayHasKey('USD', $result);
        $this->assertArrayHasKey('TND', $result);
        $this->assertSame(100.0, $result['TND']);
    }

    // ── estSupportee ────────────────────────────────────────────────────────

    public function testEstSupporteeKnownCurrencies(): void
    {
        foreach (['TND', 'EUR', 'USD', 'GBP', 'SAR', 'MAD', 'CAD', 'JPY', 'CHF', 'AED'] as $code) {
            $this->assertTrue($this->service->estSupportee($code), "$code should be supported");
        }
    }

    public function testEstSupporteeUnknownCurrency(): void
    {
        $this->assertFalse($this->service->estSupportee('XYZ'));
        $this->assertFalse($this->service->estSupportee('BTC'));
    }

    public function testEstSupporteeIsCaseInsensitive(): void
    {
        $this->assertTrue($this->service->estSupportee('eur'));
        $this->assertTrue($this->service->estSupportee('Usd'));
    }

    // ── getMetaDevise ────────────────────────────────────────────────────────

    public function testGetMetaDeviseEur(): void
    {
        $meta = $this->service->getMetaDevise('EUR');

        $this->assertSame('Euro', $meta['nom']);
        $this->assertSame('€', $meta['symbole']);
        $this->assertSame(2, $meta['decimales']);
    }

    public function testGetMetaDeviseUnknownReturnsEmpty(): void
    {
        $meta = $this->service->getMetaDevise('XYZ');
        $this->assertSame([], $meta);
    }

    // ── getOptionsList ────────────────────────────────────────────────────────

    public function testGetOptionsListContainsAllCurrencies(): void
    {
        $options = $this->service->getOptionsList();

        $this->assertCount(10, $options);
        $this->assertArrayHasKey('TND', $options);
        $this->assertArrayHasKey('EUR', $options);
        $this->assertStringContainsString('Euro', $options['EUR']);
    }

    // ── formater ────────────────────────────────────────────────────────────

    public function testFormaterWithoutSymbole(): void
    {
        $this->cache->method('get')->willThrowException(new \RuntimeException());
        $this->logger->method('warning');

        $result = $this->service->formater(100.0, 'TND', false);
        $this->assertStringNotContainsString('TND', $result);
    }

    public function testFormaterEurSymbolIsAppended(): void
    {
        $this->cache->method('get')->willThrowException(new \RuntimeException());
        $this->logger->method('warning');

        $result = $this->service->formater(100.0, 'EUR');
        $this->assertStringEndsWith('€', $result);
    }

    public function testFormaterUsdSymbolIsPrepended(): void
    {
        $this->cache->method('get')->willThrowException(new \RuntimeException());
        $this->logger->method('warning');

        $result = $this->service->formater(100.0, 'USD');
        $this->assertStringStartsWith('$', $result);
    }

    // ── invaliderCache ───────────────────────────────────────────────────────

    public function testInvaliderCacheCallsDelete(): void
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('currency_rates_tnd');

        $this->logger->expects($this->once())->method('info');

        $this->service->invaliderCache();
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function buildFakeCache(): array
    {
        return [
            'rates'      => array_combine(
                array_keys(CurrencyService::DEVISES),
                [1.0, 0.2965, 0.3200, 0.2540, 1.2005, 3.2100, 0.4390, 47.850, 0.2890, 1.1750]
            ),
            'source'     => 'fake',
            'updated_at' => '2026-04-27T00:00:00Z',
        ];
    }
}
