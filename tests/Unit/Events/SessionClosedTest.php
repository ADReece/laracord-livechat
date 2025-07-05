<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Events;

use ADReece\LaracordLiveChat\Events\SessionClosed;
use ADReece\LaracordLiveChat\Models\ChatSession;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class SessionClosedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_event_with_session()
    {
        $session = ChatSession::factory()->create();

        $event = new SessionClosed($session);

        $this->assertSame($session, $event->session);
    }

    /** @test */
    public function it_broadcasts_on_correct_channel()
    {
        $session = ChatSession::factory()->create(['id' => 789]);

        $event = new SessionClosed($session);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals('chat-session.789', $channels[0]->name);
    }

    /** @test */
    public function it_broadcasts_with_correct_data()
    {
        $closedAt = Carbon::now();
        $session = ChatSession::factory()->create([
            'customer_name' => 'Bob Johnson',
            'status' => 'closed',
            'closed_at' => $closedAt,
        ]);

        $event = new SessionClosed($session);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('session', $broadcastData);
        $this->assertEquals($session->id, $broadcastData['session']['id']);
        $this->assertEquals('Bob Johnson', $broadcastData['session']['customer_name']);
        $this->assertEquals('closed', $broadcastData['session']['status']);
        $this->assertEquals($closedAt->toISOString(), $broadcastData['session']['closed_at']);
    }

    /** @test */
    public function it_broadcasts_as_correct_event_name()
    {
        $session = ChatSession::factory()->create();

        $event = new SessionClosed($session);

        $this->assertEquals('session.closed', $event->broadcastAs());
    }

    /** @test */
    public function it_implements_should_broadcast_interface()
    {
        $session = ChatSession::factory()->create();

        $event = new SessionClosed($session);

        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $event);
    }

    /** @test */
    public function it_can_be_listened_to()
    {
        Event::fake();

        $session = ChatSession::factory()->create();

        event(new SessionClosed($session));

        Event::assertDispatched(SessionClosed::class, function ($event) use ($session) {
            return $event->session->id === $session->id;
        });
    }

    /** @test */
    public function it_includes_session_duration_in_broadcast_data()
    {
        $startedAt = Carbon::now()->subHour();
        $closedAt = Carbon::now();
        
        $session = ChatSession::factory()->create([
            'started_at' => $startedAt,
            'closed_at' => $closedAt,
        ]);

        $event = new SessionClosed($session);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('session', $broadcastData);
        $this->assertArrayHasKey('started_at', $broadcastData['session']);
        $this->assertArrayHasKey('closed_at', $broadcastData['session']);
    }

    /** @test */
    public function it_includes_final_message_count()
    {
        $session = ChatSession::factory()->create();
        
        // Add some messages to the session
        $session->messages()->createMany([
            ['content' => 'Message 1', 'sender_type' => 'customer'],
            ['content' => 'Message 2', 'sender_type' => 'agent'],
            ['content' => 'Message 3', 'sender_type' => 'customer'],
        ]);

        $event = new SessionClosed($session);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('session', $broadcastData);
        $this->assertArrayHasKey('messages_count', $broadcastData['session']);
        $this->assertEquals(3, $broadcastData['session']['messages_count']);
    }

    /** @test */
    public function it_handles_sessions_closed_without_discord_channel()
    {
        $session = ChatSession::factory()->create([
            'discord_channel_id' => null,
            'status' => 'closed',
        ]);

        $event = new SessionClosed($session);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('session', $broadcastData);
        $this->assertNull($broadcastData['session']['discord_channel_id']);
    }

    /** @test */
    public function it_includes_reason_for_closure_if_available()
    {
        $session = ChatSession::factory()->create([
            'status' => 'closed',
            'closure_reason' => 'Resolved by customer',
        ]);

        $event = new SessionClosed($session);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('session', $broadcastData);
        $this->assertEquals('Resolved by customer', $broadcastData['session']['closure_reason']);
    }

    /** @test */
    public function it_works_with_sessions_that_have_discord_channels()
    {
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '987654321',
            'status' => 'closed',
        ]);

        $event = new SessionClosed($session);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('session', $broadcastData);
        $this->assertEquals('987654321', $broadcastData['session']['discord_channel_id']);
    }

    /** @test */
    public function it_preserves_customer_information()
    {
        $session = ChatSession::factory()->create([
            'customer_name' => 'Alice Cooper',
            'customer_email' => 'alice@example.com',
            'status' => 'closed',
        ]);

        $event = new SessionClosed($session);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('session', $broadcastData);
        $this->assertEquals('Alice Cooper', $broadcastData['session']['customer_name']);
        $this->assertEquals('alice@example.com', $broadcastData['session']['customer_email']);
    }

    /** @test */
    public function it_handles_automatically_closed_sessions()
    {
        $session = ChatSession::factory()->create([
            'status' => 'closed',
            'closure_reason' => 'Automatically closed due to inactivity',
            'closed_at' => Carbon::now(),
        ]);

        $event = new SessionClosed($session);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('session', $broadcastData);
        $this->assertEquals('closed', $broadcastData['session']['status']);
        $this->assertStringContainsString('Automatically closed', $broadcastData['session']['closure_reason']);
    }
}
