<?php

namespace ADReece\LaracordLiveChat\Tests\Feature\Http\Controllers;

use ADReece\LaracordLiveChat\Models\ChatMessage;
use ADReece\LaracordLiveChat\Models\ChatSession;
use ADReece\LaracordLiveChat\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class DiscordControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_handles_discord_webhook_messages()
    {
        Event::fake();
        
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
            'status' => 'active',
        ]);

        $webhookData = [
            'id' => 'msg123',
            'content' => 'Hello from Discord agent',
            'channel_id' => '123456789',
            'author' => [
                'id' => 'agent456',
                'username' => 'support_agent',
                'bot' => false,
            ],
            'timestamp' => now()->toISOString(),
        ];

        $response = $this->postJson(route('laracord.discord.webhook'), $webhookData);

        $response->assertStatus(200);

        // Check that message was stored
        $this->assertDatabaseHas('chat_messages', [
            'chat_session_id' => $session->id,
            'discord_message_id' => 'msg123',
            'content' => 'Hello from Discord agent',
            'sender_type' => 'agent',
        ]);

        // Check that MessageSent event was fired
        Event::assertDispatched(\ADReece\LaracordLiveChat\Events\MessageSent::class);
    }

    /** @test */
    public function it_ignores_bot_messages_from_webhook()
    {
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
            'status' => 'active',
        ]);

        $webhookData = [
            'id' => 'bot_msg',
            'content' => 'Bot message',
            'channel_id' => '123456789',
            'author' => [
                'id' => 'bot123',
                'username' => 'chatbot',
                'bot' => true,
            ],
            'timestamp' => now()->toISOString(),
        ];

        $response = $this->postJson(route('laracord.discord.webhook'), $webhookData);

        $response->assertStatus(200);

        // Bot message should not be stored
        $this->assertDatabaseMissing('chat_messages', [
            'discord_message_id' => 'bot_msg',
        ]);
    }

    /** @test */
    public function it_ignores_messages_for_unknown_channels()
    {
        $webhookData = [
            'id' => 'msg123',
            'content' => 'Message for unknown channel',
            'channel_id' => '999999999',
            'author' => [
                'id' => 'agent456',
                'username' => 'support_agent',
                'bot' => false,
            ],
            'timestamp' => now()->toISOString(),
        ];

        $response = $this->postJson(route('laracord.discord.webhook'), $webhookData);

        $response->assertStatus(200);

        // Message should not be stored
        $this->assertDatabaseMissing('chat_messages', [
            'discord_message_id' => 'msg123',
        ]);
    }

    /** @test */
    public function it_ignores_messages_for_closed_sessions()
    {
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
            'status' => 'closed',
        ]);

        $webhookData = [
            'id' => 'msg123',
            'content' => 'Message for closed session',
            'channel_id' => '123456789',
            'author' => [
                'id' => 'agent456',
                'username' => 'support_agent',
                'bot' => false,
            ],
            'timestamp' => now()->toISOString(),
        ];

        $response = $this->postJson(route('laracord.discord.webhook'), $webhookData);

        $response->assertStatus(200);

        // Message should not be stored for closed session
        $this->assertDatabaseMissing('chat_messages', [
            'discord_message_id' => 'msg123',
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_messages()
    {
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
            'status' => 'active',
        ]);

        // Create existing message
        ChatMessage::factory()->create([
            'chat_session_id' => $session->id,
            'discord_message_id' => 'existing_msg',
            'content' => 'Existing message',
            'sender_type' => 'agent',
        ]);

        $webhookData = [
            'id' => 'existing_msg',
            'content' => 'Existing message',
            'channel_id' => '123456789',
            'author' => [
                'id' => 'agent456',
                'username' => 'support_agent',
                'bot' => false,
            ],
            'timestamp' => now()->toISOString(),
        ];

        $response = $this->postJson(route('laracord.discord.webhook'), $webhookData);

        $response->assertStatus(200);

        // Should still only have one message with this ID
        $this->assertEquals(1, ChatMessage::where('discord_message_id', 'existing_msg')->count());
    }

    /** @test */
    public function it_handles_malformed_webhook_data()
    {
        $malformedData = [
            'invalid' => 'data',
        ];

        $response = $this->postJson(route('laracord.discord.webhook'), $malformedData);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_handles_missing_required_fields()
    {
        $incompleteData = [
            'id' => 'msg123',
            'content' => 'Message without channel',
            // Missing channel_id
            'author' => [
                'id' => 'agent456',
                'username' => 'support_agent',
                'bot' => false,
            ],
        ];

        $response = $this->postJson(route('laracord.discord.webhook'), $incompleteData);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_validates_webhook_signature_if_configured()
    {
        config(['laracord-live-chat.discord.webhook_secret' => 'test_secret']);

        $webhookData = [
            'id' => 'msg123',
            'content' => 'Test message',
            'channel_id' => '123456789',
            'author' => [
                'id' => 'agent456',
                'username' => 'support_agent',
                'bot' => false,
            ],
            'timestamp' => now()->toISOString(),
        ];

        // Request without proper signature
        $response = $this->postJson(route('laracord.discord.webhook'), $webhookData);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_accepts_valid_webhook_signature()
    {
        config(['laracord-live-chat.discord.webhook_secret' => 'test_secret']);
        
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
            'status' => 'active',
        ]);

        $webhookData = [
            'id' => 'msg123',
            'content' => 'Test message',
            'channel_id' => '123456789',
            'author' => [
                'id' => 'agent456',
                'username' => 'support_agent',
                'bot' => false,
            ],
            'timestamp' => now()->toISOString(),
        ];

        $payload = json_encode($webhookData);
        $signature = hash_hmac('sha256', $payload, 'test_secret');

        $response = $this->postJson(
            route('laracord.discord.webhook'),
            $webhookData,
            ['X-Signature-256' => 'sha256=' . $signature]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('chat_messages', [
            'discord_message_id' => 'msg123',
        ]);
    }

    /** @test */
    public function it_handles_message_content_encoding()
    {
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
            'status' => 'active',
        ]);

        $webhookData = [
            'id' => 'msg123',
            'content' => 'Message with émojis 🎉 and special chars àáâã',
            'channel_id' => '123456789',
            'author' => [
                'id' => 'agent456',
                'username' => 'support_agent',
                'bot' => false,
            ],
            'timestamp' => now()->toISOString(),
        ];

        $response = $this->postJson(route('laracord.discord.webhook'), $webhookData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('chat_messages', [
            'discord_message_id' => 'msg123',
            'content' => 'Message with émojis 🎉 and special chars àáâã',
        ]);
    }

    /** @test */
    public function it_updates_session_last_activity()
    {
        $session = ChatSession::factory()->create([
            'discord_channel_id' => '123456789',
            'status' => 'active',
            'last_activity' => now()->subHour(),
        ]);

        $originalLastActivity = $session->last_activity;

        $webhookData = [
            'id' => 'msg123',
            'content' => 'New agent message',
            'channel_id' => '123456789',
            'author' => [
                'id' => 'agent456',
                'username' => 'support_agent',
                'bot' => false,
            ],
            'timestamp' => now()->toISOString(),
        ];

        $response = $this->postJson(route('laracord.discord.webhook'), $webhookData);

        $response->assertStatus(200);

        $session->refresh();
        $this->assertTrue($session->last_activity->gt($originalLastActivity));
    }
}
