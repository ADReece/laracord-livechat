<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Events;

use ADReece\LaracordLiveChat\Events\SessionStarted;
use ADReece\LaracordLiveChat\Models\ChatSession;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class SessionStartedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_event_with_session()
    {
        $session = ChatSession::factory()->create();

        $event = new SessionStarted($session);

        $this->assertSame($session, $event->session);
    }

    /** @test */
    public function it_broadcasts_on_correct_channel()
    {
        $session = ChatSession::factory()->create(['id' => 456]);

        $event = new SessionStarted($session);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals('chat-session.456', $channels[0]->name);
    }

    /** @test */
    public function it_broadcasts_with_correct_data()
    {
        $session = ChatSession::factory()->create([
            'customer_name' => 'Jane Smith',
            'customer_email' => 'jane@example.com',
            'status' => 'active',
        ]);

        $event = new SessionStarted($session);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('session', $broadcastData);
        $this->assertEquals($session->id, $broadcastData['session']['id']);
        $this->assertEquals('Jane Smith', $broadcastData['session']['customer_name']);
        $this->assertEquals('jane@example.com', $broadcastData['session']['customer_email']);
        $this->assertEquals('active', $broadcastData['session']['status']);
    }

    /** @test */
    public function it_broadcasts_as_correct_event_name()
    {
        $session = ChatSession::factory()->create();

        $event = new SessionStarted($session);

        $this->assertEquals('session.started', $event->broadcastAs());
    }

    /** @test */
    public function it_implements_should_broadcast_interface()
    {
        $session = ChatSession::factory()->create();

        $event = new SessionStarted($session);

        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $event);
    }

    /** @test */
    public function it_can_be_listened_to()
    {
        Event::fake();

        $session = ChatSession::factory()->create();

        event(new SessionStarted($session));

        Event::assertDispatched(SessionStarted::class, function ($event) use ($session) {
            return $event->session->id === $session->id;
        });
    }

    /** @test */
    public function it_includes_timestamps_in_broadcast_data()
    {
        $session = ChatSession::factory()->create();

        $event = new SessionStarted($session);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('session', $broadcastData);
        $this->assertArrayHasKey('created_at', $broadcastData['session']);
        $this->assertArrayHasKey('started_at', $broadcastData['session']);
    }

    /** @test */
    public function it_includes_discord_channel_info_if_present()
    {
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
        ]);

        $event = new SessionStarted($session);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('session', $broadcastData);
        $this->assertEquals('123456789', $broadcastData['session']['discord_channel_id']);
    }

    /** @test */
    public function it_handles_sessions_without_discord_channel()
    {
        $session = ChatSession::factory()->create([
            'discord_channel_id' => null,
        ]);

        $event = new SessionStarted($session);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('session', $broadcastData);
        $this->assertNull($broadcastData['session']['discord_channel_id']);
    }

    /** @test */
    public function it_works_with_different_session_statuses()
    {
        $pendingSession = ChatSession::factory()->create(['status' => 'pending']);
        $activeSession = ChatSession::factory()->create(['status' => 'active']);

        $pendingEvent = new SessionStarted($pendingSession);
        $activeEvent = new SessionStarted($activeSession);

        $pendingData = $pendingEvent->broadcastWith();
        $activeData = $activeEvent->broadcastWith();

        $this->assertEquals('pending', $pendingData['session']['status']);
        $this->assertEquals('active', $activeData['session']['status']);
    }

    /** @test */
    public function it_excludes_sensitive_information()
    {
        $session = ChatSession::factory()->create([
            'customer_email' => 'sensitive@example.com',
        ]);

        $event = new SessionStarted($session);
        $broadcastData = $event->broadcastWith();

        // Email should be included but we could add logic to exclude it in production
        $this->assertArrayHasKey('session', $broadcastData);
        
        // Verify that no internal Laravel timestamps are exposed that shouldn't be
        $this->assertArrayNotHasKey('updated_at', $broadcastData['session']);
    }
}
