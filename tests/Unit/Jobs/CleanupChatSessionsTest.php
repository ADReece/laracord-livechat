<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Jobs;

use ADReece\LaracordLiveChat\Jobs\CleanupChatSessions;
use ADReece\LaracordLiveChat\Models\ChatSession;
use ADReece\LaracordLiveChat\Services\DiscordService;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class CleanupChatSessionsTest extends TestCase
{
    use RefreshDatabase;

    private $discordService;
    private $job;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->discordService = Mockery::mock(DiscordService::class);
        $this->app->instance(DiscordService::class, $this->discordService);
        
        $this->job = new CleanupChatSessions();
    }

    /** @test */
    public function it_cleans_up_inactive_sessions()
    {
        // Create test sessions
        $activeSessions = ChatSession::factory()->count(3)->create([
            'last_activity' => Carbon::now()->subMinutes(30),
            'status' => 'active',
        ]);

        $inactiveSessions = ChatSession::factory()->count(2)->create([
            'last_activity' => Carbon::now()->subHours(2),
            'status' => 'active',
        ]);

        // Mock Discord service to delete channels
        $this->discordService
            ->shouldReceive('deleteChannel')
            ->times(2)
            ->andReturn(true);

        // Execute the job
        $this->job->handle($this->discordService);

        // Assert active sessions remain unchanged
        foreach ($activeSessions as $session) {
            $this->assertDatabaseHas('chat_sessions', [
                'id' => $session->id,
                'status' => 'active',
            ]);
        }

        // Assert inactive sessions are closed
        foreach ($inactiveSessions as $session) {
            $this->assertDatabaseHas('chat_sessions', [
                'id' => $session->id,
                'status' => 'closed',
                'closed_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        }
    }

    /** @test */
    public function it_handles_discord_deletion_failure_gracefully()
    {
        $inactiveSession = ChatSession::factory()->create([
            'last_activity' => Carbon::now()->subHours(2),
            'status' => 'active',
            'discord_channel_id' => '123456789',
        ]);

        // Mock Discord service to fail deletion
        $this->discordService
            ->shouldReceive('deleteChannel')
            ->once()
            ->with('123456789')
            ->andReturn(false);

        // Execute the job - should not throw exception
        $this->job->handle($this->discordService);

        // Session should still be closed even if Discord deletion fails
        $this->assertDatabaseHas('chat_sessions', [
            'id' => $inactiveSession->id,
            'status' => 'closed',
        ]);
    }

    /** @test */
    public function it_only_processes_active_sessions()
    {
        // Create sessions with different statuses
        ChatSession::factory()->create([
            'last_activity' => Carbon::now()->subHours(2),
            'status' => 'closed',
        ]);

        ChatSession::factory()->create([
            'last_activity' => Carbon::now()->subHours(2),
            'status' => 'pending',
        ]);

        // Mock should not be called since no active sessions to clean up
        $this->discordService
            ->shouldNotReceive('deleteChannel');

        $this->job->handle($this->discordService);

        // No changes should be made
        $this->assertEquals(2, ChatSession::count());
    }

    /** @test */
    public function it_uses_configurable_timeout()
    {
        config(['laracord-live-chat.session_timeout' => 30]); // 30 minutes

        $recentInactiveSession = ChatSession::factory()->create([
            'last_activity' => Carbon::now()->subMinutes(45),
            'status' => 'active',
        ]);

        $oldInactiveSession = ChatSession::factory()->create([
            'last_activity' => Carbon::now()->subMinutes(25),
            'status' => 'active',
        ]);

        $this->discordService
            ->shouldReceive('deleteChannel')
            ->once(); // Only one session should be cleaned up

        $this->job->handle($this->discordService);

        // Only the session older than 30 minutes should be closed
        $this->assertDatabaseHas('chat_sessions', [
            'id' => $recentInactiveSession->id,
            'status' => 'closed',
        ]);

        $this->assertDatabaseHas('chat_sessions', [
            'id' => $oldInactiveSession->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_handles_sessions_without_discord_channels()
    {
        $inactiveSession = ChatSession::factory()->create([
            'last_activity' => Carbon::now()->subHours(2),
            'status' => 'active',
            'discord_channel_id' => null,
        ]);

        // Should not try to delete Discord channel
        $this->discordService
            ->shouldNotReceive('deleteChannel');

        $this->job->handle($this->discordService);

        $this->assertDatabaseHas('chat_sessions', [
            'id' => $inactiveSession->id,
            'status' => 'closed',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
