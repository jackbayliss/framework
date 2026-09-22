<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Queue\Attributes\Delay;
use Illuminate\Queue\BeanstalkdQueue;
use Illuminate\Queue\Jobs\BeanstalkdJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Mockery;
use Pheanstalk\Contract\JobIdInterface;
use Pheanstalk\Contract\PheanstalkManagerInterface;
use Pheanstalk\Contract\PheanstalkPublisherInterface;
use Pheanstalk\Contract\PheanstalkSubscriberInterface;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\Job;
use Pheanstalk\Values\ServerStats;
use Pheanstalk\Values\TubeList;
use Pheanstalk\Values\TubeName;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class QueueBeanstalkdQueueTest extends TestCase
{
    /**
     * @var \Illuminate\Queue\BeanstalkdQueue
     */
    private $queue;

    /**
     * @var \Illuminate\Container\Container|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $container;

    public function testPushProperlyPushesJobOntoBeanstalkd()
    {
        $uuid = Str::uuid();

        $time = Carbon::now();
        Carbon::setTestNow($time);

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $this->setQueue('default', 60);
        $pheanstalk = $this->queue->getPheanstalk();
        $pheanstalk->expects('useTube')->with(Mockery::type(TubeName::class));
        $pheanstalk->expects('useTube')->with(Mockery::type(TubeName::class));
        $pheanstalk->expects('put')->times(2)->with(json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'createdAt' => $time->getTimestamp(), 'delay' => null]), 1024, 0, 60);

        $this->queue->push('foo', ['data'], 'stack');
        $this->queue->push('foo', ['data']);

        $this->container->shouldHaveReceived('bound')->with('events')->times(4);

        Str::createUuidsNormally();
    }

    public function testDelayedPushProperlyPushesJobOntoBeanstalkd()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(function () use ($uuid) {
            return $uuid;
        });

        $time = Carbon::now();
        Carbon::setTestNow($time);

        $this->setQueue('default', 60);
        $pheanstalk = $this->queue->getPheanstalk();
        $pheanstalk->expects('useTube')->with(Mockery::type(TubeName::class));
        $pheanstalk->expects('useTube')->with(Mockery::type(TubeName::class));
        $pheanstalk->expects('put')->times(2)->with(json_encode(['uuid' => $uuid, 'displayName' => 'foo', 'job' => 'foo', 'maxTries' => null, 'maxExceptions' => null, 'failOnTimeout' => false, 'backoff' => null, 'timeout' => null, 'data' => ['data'], 'createdAt' => $time->getTimestamp(), 'delay' => 5]), Pheanstalk::DEFAULT_PRIORITY, 5, Pheanstalk::DEFAULT_TTR);

        $this->queue->later(5, 'foo', ['data'], 'stack');
        $this->queue->later(5, 'foo', ['data']);

        $this->container->shouldHaveReceived('bound')->with('events')->times(4);

        Str::createUuidsNormally();
    }

    public function testBulkRespectsDelayAttributeWhenPushingOntoBeanstalkd()
    {
        $this->setQueue('default', 60);
        $pheanstalk = $this->queue->getPheanstalk();
        $pheanstalk->expects('useTube')->with(Mockery::type(TubeName::class));
        $pheanstalk->expects('put')->with(Mockery::type('string'), Pheanstalk::DEFAULT_PRIORITY, 15, Pheanstalk::DEFAULT_TTR);

        $this->queue->bulk([new BeanstalkdJobWithDelayAttribute], ['data']);
    }

    public function testPopProperlyPopsJobOffOfBeanstalkd()
    {
        $this->setQueue('default', 60);
        $tube = new TubeName('default');

        $pheanstalk = $this->queue->getPheanstalk();
        $pheanstalk->expects('watch')->with(Mockery::type(TubeName::class));
        $pheanstalk->expects('listTubesWatched')->andReturn(new TubeList($tube));

        $jobId = Mockery::mock(JobIdInterface::class);
        $jobId->expects('getId');
        $job = new Job($jobId, '');
        $pheanstalk->expects('reserveWithTimeout')->with(0)->andReturn($job);

        $result = $this->queue->pop();

        $this->assertInstanceOf(BeanstalkdJob::class, $result);
    }

    public function testBlockingPopProperlyPopsJobOffOfBeanstalkd()
    {
        $this->setQueue('default', 60, 60);
        $tube = new TubeName('default');

        $pheanstalk = $this->queue->getPheanstalk();
        $pheanstalk->expects('watch')->with(Mockery::type(TubeName::class));
        $pheanstalk->expects('listTubesWatched')->andReturn(new TubeList($tube));

        $jobId = Mockery::mock(JobIdInterface::class);
        $jobId->expects('getId');
        $job = new Job($jobId, '');
        $pheanstalk->expects('reserveWithTimeout')->with(60)->andReturn($job);

        $result = $this->queue->pop();

        $this->assertInstanceOf(BeanstalkdJob::class, $result);
    }

    public function testDeleteProperlyRemoveJobsOffBeanstalkd()
    {
        $this->setQueue('default', 60);

        $pheanstalk = $this->queue->getPheanstalk();
        $pheanstalk->expects('useTube')->with(Mockery::type(TubeName::class))->andReturn($pheanstalk);
        $pheanstalk->expects('delete')->with(Mockery::type(JobIdInterface::class));

        $this->queue->deleteMessage('default', 1);
    }

    public function testTotalSizesAreReadFromTheServerStats()
    {
        $this->setQueue('default', 60);
        $this->queue->getPheanstalk()->allows('stats')->andReturn($this->serverStats([
            'currentJobsReady' => 3,
            'currentJobsDelayed' => 2,
            'currentJobsReserved' => 1,
        ]));

        $this->assertSame(6, $this->queue->totalSize());
        $this->assertSame(3, $this->queue->totalPendingSize());
        $this->assertSame(2, $this->queue->totalDelayedSize());
        $this->assertSame(1, $this->queue->totalReservedSize());
    }

    private function serverStats(array $stats)
    {
        $parameters = (new ReflectionMethod(ServerStats::class, '__construct'))->getParameters();

        return new ServerStats(...array_merge(
            array_fill_keys(array_map(fn ($parameter) => $parameter->getName(), $parameters), 0),
            $stats,
        ));
    }

    /**
     * @param  string  $default
     * @param  int  $timeToRun
     * @param  int  $blockFor
     */
    private function setQueue($default, $timeToRun, $blockFor = 0)
    {
        $this->queue = new BeanstalkdQueue(
            Mockery::mock(implode(',', [PheanstalkManagerInterface::class, PheanstalkPublisherInterface::class, PheanstalkSubscriberInterface::class])),
            $default,
            $timeToRun,
            $blockFor
        );
        $this->container = Mockery::spy(Container::class);
        $this->queue->setContainer($this->container);
    }
}

#[Delay(15)]
class BeanstalkdJobWithDelayAttribute
{
}
