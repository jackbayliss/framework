<?php

namespace Illuminate\Tests\Foundation\Cloud;

use Illuminate\Foundation\Cloud\CloudManager;
use Illuminate\Foundation\CloudBootstrapper;
use Illuminate\Support\Facades\Cloud;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;

class CloudManagerTest extends TestCase
{
    #[TestWith([null, false])]
    #[TestWith(['sqs', false])]
    #[TestWith(['cloud', true])]
    public function testUsesManagedQueuesReflectsTheCloudConnectionDriver(?string $driver, bool $managed)
    {
        config(['queue.connections.cloud.driver' => $driver]);

        $this->assertSame($managed, Cloud::usesManagedQueues());
    }

    public function testQueueThrowsWhenManagedQueuesAreNotConfigured()
    {
        $this->expectExceptionObject(new RuntimeException(
            'Laravel Cloud managed queues are not configured for this application.'
        ));

        Cloud::queue();
    }

    #[TestWith(['default', true])]
    #[TestWith(['emails', true])]
    #[TestWith(['reports', false])]
    public function testManagesQueueChecksTheConfiguredManagedQueues(string $queue, bool $managed)
    {
        config([
            'queue.connections.cloud' => [
                'driver' => 'cloud',
                'queues' => ['default', 'emails'],
                'connection' => [
                    'driver' => 'sqs',
                    'region' => 'us-east-2',
                    'prefix' => 'https://sqs.us-east-2.amazonaws.com/1234567',
                    'queue' => 'default',
                ],
            ],
        ]);

        CloudBootstrapper::registerEvents($this->app);
        CloudBootstrapper::bootManagedQueues($this->app);

        $this->assertSame($managed, Cloud::managesQueue($queue));
    }

    public function testFacadeResolvesTheCloudManager()
    {
        $this->assertInstanceOf(CloudManager::class, Cloud::getFacadeRoot());
    }

    public function testCloudManagerIsMacroable()
    {
        CloudManager::macro('foo', fn () => 'bar');

        $this->assertSame('bar', Cloud::foo());
    }
}
