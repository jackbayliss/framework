<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Cache\Events\CacheFallbackStoreFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;

#[WithConfig('cache.default', 'fallback')]
#[WithConfig('cache.stores.array.serialize', false)]
class FallbackStoreTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        CantSerializeFallback::$throwException = true;
    }

    public function testFallbackCacheWritesToAllStoresAndDispatchesEventOnlyOnce()
    {
        config([
            'cache.stores.failing_array' => array_merge(config('cache.stores.array'), ['serialize' => true]),
            'cache.stores.fallback' => ['driver' => 'fallback', 'stores' => ['array', 'failing_array']],
        ]);

        Event::fake();

        Cache::put('irrelevant', new CantSerializeFallback());

        Event::assertDispatched(CacheFallbackStoreFailed::class, function (CacheFallbackStoreFailed $event) {
            return $event->storeName === 'failing_array';
        });
        $this->assertInstanceOf(CantSerializeFallback::class, Cache::store('array')->get('irrelevant'));

        Cache::put('irrelevant2', new CantSerializeFallback());
        Event::assertDispatchedTimes(CacheFallbackStoreFailed::class, 1);
        CantSerializeFallback::$throwException = false;
        Cache::put('irrelevant3', new CantSerializeFallback());
        Event::assertDispatchedTimes(CacheFallbackStoreFailed::class, 1);
        CantSerializeFallback::$throwException = true;
        Cache::put('irrelevant4', new CantSerializeFallback());
        Event::assertDispatchedTimes(CacheFallbackStoreFailed::class, 2);
    }
}

class CantSerializeFallback
{
    public static bool $throwException = true;

    public function __serialize()
    {
        if (self::$throwException) {
            throw new \Exception('You cannot serialize this.');
        }

        return [];
    }
}
