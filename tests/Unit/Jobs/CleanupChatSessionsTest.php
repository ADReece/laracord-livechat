<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Jobs;

use ADReece\LaracordLiveChat\Jobs\CleanupChatSessions;
use ADReece\LaracordLiveChat\Services\ChatService;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class CleanupChatSessionsTest extends TestCase
{
    use RefreshDatabase;

    private $chatService;
    private $job;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->chatService = Mockery::mock(ChatService::class);
        $this->app->instance(ChatService::class, $this->chatService);

        $this->job = new CleanupChatSessions();
    }

    /** @test */
    public function it_calls_cleanup_on_chat_service()
    {
        $this->chatService
            ->shouldReceive('cleanup')
            ->once()
            ->andReturn(true);

        $this->job->handle($this->chatService);

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
        $this->chatService
            ->shouldReceive('cleanup')
            ->once();

        CleanupChatSessions::dispatch();

        // Add assertion to make test non-risky
        $this->assertTrue(true, 'Job dispatched successfully');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
