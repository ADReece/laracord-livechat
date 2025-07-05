<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Events;

use ADReece\LaracordLiveChat\Events\MessageSent;
use ADReece\LaracordLiveChat\Models\ChatMessage;
use ADReece\LaracordLiveChat\Models\ChatSession;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class MessageSentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_event_with_message()
    {
        $session = ChatSession::factory()->create();
        $message = ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
        ]);

        $event = new MessageSent($message);

        $this->assertSame($message, $event->message);
    }

    /** @test */
    public function it_broadcasts_on_correct_channel()
    {
        $session = ChatSession::factory()->create(['id' => 123]);
        $message = ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
        ]);

        $event = new MessageSent($message);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals('chat-session.123', $channels[0]->name);
    }

    /** @test */
    public function it_broadcasts_with_correct_data()
    {
        $session = ChatSession::factory()->create();
        $message = ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
            'content' => 'Test message content',
            'sender_type' => 'customer',
            'sender_name' => 'John Doe',
        ]);

        $event = new MessageSent($message);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('message', $broadcastData);
        $this->assertEquals($message->id, $broadcastData['message']['id']);
        $this->assertEquals('Test message content', $broadcastData['message']['content']);
        $this->assertEquals('customer', $broadcastData['message']['sender_type']);
        $this->assertEquals('John Doe', $broadcastData['message']['sender_name']);
    }

    /** @test */
    public function it_broadcasts_as_correct_event_name()
    {
        $session = ChatSession::factory()->create();
        $message = ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
        ]);

        $event = new MessageSent($message);

        $this->assertEquals('message.sent', $event->broadcastAs());
    }

    /** @test */
    public function it_implements_should_broadcast_interface()
    {
        $session = ChatSession::factory()->create();
        $message = ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
        ]);

        $event = new MessageSent($message);

        $this->assertInstanceOf(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $event);
    }

    /** @test */
    public function it_can_be_listened_to()
    {
        Event::fake();

        $session = ChatSession::factory()->create();
        $message = ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
        ]);

        event(new MessageSent($message));

        Event::assertDispatched(MessageSent::class, function ($event) use ($message) {
            return $event->message->id === $message->id;
        });
    }

    /** @test */
    public function it_includes_timestamp_in_broadcast_data()
    {
        $session = ChatSession::factory()->create();
        $message = ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
        ]);

        $event = new MessageSent($message);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('message', $broadcastData);
        $this->assertArrayHasKey('created_at', $broadcastData['message']);
        $this->assertNotNull($broadcastData['message']['created_at']);
    }

    /** @test */
    public function it_works_with_different_sender_types()
    {
        $session = ChatSession::factory()->create();
        
        $customerMessage = ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
            'sender_type' => 'customer',
        ]);

        $agentMessage = ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
            'sender_type' => 'agent',
        ]);

        $customerEvent = new MessageSent($customerMessage);
        $agentEvent = new MessageSent($agentMessage);

        $customerData = $customerEvent->broadcastWith();
        $agentData = $agentEvent->broadcastWith();

        $this->assertEquals('customer', $customerData['message']['sender_type']);
        $this->assertEquals('agent', $agentData['message']['sender_type']);
    }

    /** @test */
    public function it_handles_messages_with_null_values()
    {
        $session = ChatSession::factory()->create();
        $message = ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
            'sender_name' => null,
            'discord_message_id' => null,
        ]);

        $event = new MessageSent($message);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('message', $broadcastData);
        $this->assertNull($broadcastData['message']['sender_name']);
        $this->assertNull($broadcastData['message']['discord_message_id']);
    }
}
