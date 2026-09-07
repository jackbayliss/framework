<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Foundation\Events\Terminating;
use Illuminate\Tests\Foundation\Console\Fixtures\DiscoverableCommand;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\StringInput;

class KernelTest extends TestCase
{
    public function testItDispatchesTerminatingEvent()
    {
        $called = [];
        $app = new Application;
        $events = new Dispatcher($app);
        $app->instance('events', $events);
        $kernel = new Kernel($app, $events);
        $events->listen(function (Terminating $terminating) use (&$called) {
            $called[] = 'terminating event';
        });
        $app->terminating(function () use (&$called) {
            $called[] = 'terminating callback';
        });

        $kernel->terminate(new StringInput('tinker'), 0);

        $this->assertSame([
            'terminating event',
            'terminating callback',
        ], $called);
    }

    public function testFindCommandReturnsNullWhenTheCommandDoesNotExist()
    {
        $kernel = $this->makeKernel();

        $command = $kernel->findCommand('not-a-real-command');

        $this->assertNull($command);
    }

    public function testFindCommandRetrievesTheCommand()
    {
        $kernel = $this->makeKernel();
        $kernel->registerCommand(new KernelTestCommand);

        $command = $kernel->findCommand('kernel-test-command');

        $this->assertInstanceOf(KernelTestCommand::class, $command);
    }

    public function testFindCommandDoesNotResolveOtherLazilyRegisteredCommands()
    {
        KernelTestLazyCommand::$constructionAttempts = 0;
        $kernel = $this->makeKernel();
        $artisan = $this->getArtisan($kernel);
        $artisan->resolveCommands([KernelTestLazyCommand::class]);
        $artisan->setContainerCommandLoader();

        $kernel->registerCommand(new KernelTestCommand);

        $command = $kernel->findCommand('kernel-test-command');

        $this->assertInstanceOf(KernelTestCommand::class, $command);
        $this->assertSame(0, KernelTestLazyCommand::$constructionAttempts);

        $command = $kernel->findCommand('kernel-test-lazy-command');
        $this->assertSame(1, KernelTestLazyCommand::$constructionAttempts);
        $this->assertInstanceOf(KernelTestLazyCommand::class, $command);
    }

    public function testFindCommandReturnsTheSameInstanceOnSubsequentCalls()
    {
        KernelTestLazyCommand::$constructionAttempts = 0;
        $kernel = $this->makeKernel();
        $artisan = $this->getArtisan($kernel);
        $artisan->resolveCommands([KernelTestLazyCommand::class]);
        $artisan->setContainerCommandLoader();

        $first = $kernel->findCommand('kernel-test-lazy-command');
        $second = $kernel->findCommand('kernel-test-lazy-command');

        $this->assertSame($first, $second);
        $this->assertSame(1, KernelTestLazyCommand::$constructionAttempts);
    }

    public function testLoadSkipsCommandsThatOptOutOfDiscovery()
    {
        Artisan::forgetBootstrappers();

        $app = new Application;
        $app->useAppPath(__DIR__.'/Fixtures');

        (new ReflectionClass($app))->getProperty('namespace')
            ->setValue($app, 'Illuminate\\Tests\\Foundation\\Console\\Fixtures\\');

        $events = new Dispatcher($app);
        $app->instance('events', $events);
        $kernel = new Kernel($app, $events);

        (new ReflectionClass($kernel))->getMethod('load')->invoke($kernel, __DIR__.'/Fixtures');

        $this->assertInstanceOf(
            DiscoverableCommand::class,
            $kernel->findCommand('kernel-test-discoverable-command')
        );

        $this->assertNull($kernel->findCommand('kernel-test-not-discoverable-command'));

        Artisan::forgetBootstrappers();
    }

    protected function makeKernel(): Kernel
    {
        $app = new Application;
        $events = new Dispatcher($app);
        $app->instance('events', $events);

        return new Kernel($app, $events);
    }

    protected function getArtisan(Kernel $kernel)
    {
        $method = (new ReflectionClass($kernel))->getMethod('getArtisan');

        return $method->invoke($kernel);
    }
}

class KernelTestCommand extends Command
{
    protected $signature = 'kernel-test-command';

    public function handle()
    {
        //
    }
}

#[AsCommand(name: 'kernel-test-lazy-command')]
class KernelTestLazyCommand extends Command
{
    public static int $constructionAttempts = 0;

    public function __construct()
    {
        parent::__construct();

        static::$constructionAttempts++;
    }

    public function handle()
    {
        //
    }
}
