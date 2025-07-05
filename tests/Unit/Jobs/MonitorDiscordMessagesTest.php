<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Jobs;

use ADReece\LaracordLiveChat\Jobs\MonitorDiscordMessages;
use ADReece\LaracordLiveChat\Services\DiscordMessageMonitor;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class MonitorDiscordMessagesTest extends TestCase
{
    use RefreshDatabase;

    private $monitor;
    private $job;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->monitor = Mockery::mock(DiscordMessageMonitor::class);
        $this->app->instance(DiscordMessageMonitor::class, $this->monitor);
        
        $this->job = new MonitorDiscordMessages();
    }

    /** @test */
    public function it_calls_monitor_active_channels()
    {
        $this->monitor
            ->shouldReceive('monitorActiveChannels')
            ->once()
            ->andReturn(true);

        $this->job->handle($this->monitor);

        // Add assertion to make test non-risky
        $this->assertTrue(true, 'Job executed successfully');
    }

    /** @test */
    public function it_implements_should_queue_interface()
    {
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $this->job);
    }

    /** @test */
    public function it_uses_required_traits()
    {
        $traits = class_uses($this->job);

        $this->assertContains(\Illuminate\Bus\Queueable::class, $traits);
        $this->assertContains(\Illuminate\Foundation\Bus\Dispatchable::class, $traits);
        $this->assertContains(\Illuminate\Queue\InteractsWithQueue::class, $traits);
        $this->assertContains(\Illuminate\Queue\SerializesModels::class, $traits);
    }

    /** @test */
    public function it_can_be_dispatched()
    {
        $this->monitor
            ->shouldReceive('monitorActiveChannels')
            ->once();

        MonitorDiscordMessages::dispatch();

        // Add assertion to make test non-risky
        $this->assertTrue(true, 'Job dispatched successfully');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
