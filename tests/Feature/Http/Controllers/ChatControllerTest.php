<?php

namespace ADReece\LaracordLiveChat\Tests\Feature\Http\Controllers;

use ADReece\LaracordLiveChat\Tests\TestCase;
use ADReece\LaracordLiveChat\Models\ChatSession;
use ADReece\LaracordLiveChat\Models\ChatMessage;
use ADReece\LaracordLiveChat\Services\DiscordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the DiscordService
        $this->app->bind(DiscordService::class, function () {
            $mock = Mockery::mock(DiscordService::class);
            $mock->shouldReceive('createChatChannel')->andReturn('test_channel_id');
            $mock->shouldReceive('sendNewChatMessage')->andReturn(true);
            $mock->shouldReceive('deleteChatChannel')->andReturn(true);
            return $mock;
        });
    }

    /** @test */
    public function it_can_start_a_new_chat_session()
    {
        $response = $this->postJson('/api/laracord-chat/sessions', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Chat session started',
                ])
                ->assertJsonStructure([
                    'session_id',
                    'status',
                    'message',
                ]);

        $this->assertDatabaseHas('chat_sessions', [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_can_start_session_without_optional_fields()
    {
        $response = $this->postJson('/api/laracord-chat/sessions');

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                ]);

        $this->assertDatabaseHas('chat_sessions', [
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_validates_email_format_when_provided()
    {
        $response = $this->postJson('/api/laracord-chat/sessions', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_can_send_a_message()
    {
        $session = ChatSession::create([
            'customer_name' => 'John Doe',
            'ip_address' => '192.168.1.1',
            'status' => 'active',
            'discord_channel_id' => 'test_channel_id',
        ]);

        $response = $this->postJson('/api/laracord-chat/messages', [
            'session_id' => $session->id,
            'message' => 'Hello, I need help!',
            'name' => 'John Doe',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Message sent successfully',
                ])
                ->assertJsonStructure([
                    'message_id',
                    'status',
                    'message',
                ]);

        $this->assertDatabaseHas('chat_messages', [
            'session_id' => $session->id,
            'sender_type' => 'customer',
            'message' => 'Hello, I need help!',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_for_sending_message()
    {
        $response = $this->postJson('/api/laracord-chat/messages', [
            'session_id' => 'invalid-uuid',
            // Missing message
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['session_id', 'message']);
    }

    /** @test */
    public function it_prevents_sending_message_to_inactive_session()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'closed',
        ]);

        $response = $this->postJson('/api/laracord-chat/messages', [
            'session_id' => $session->id,
            'message' => 'Hello!',
        ]);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Chat session is not active or does not exist.',
                ]);
    }

    /** @test */
    public function it_can_get_session_with_messages()
    {
        $session = ChatSession::create([
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $message1 = $session->messages()->create([
            'sender_type' => 'customer',
            'sender_name' => 'John Doe',
            'message' => 'Hello!',
        ]);

        $message2 = $session->messages()->create([
            'sender_type' => 'agent',
            'sender_name' => 'Support Agent',
            'message' => 'Hi! How can I help?',
        ]);

        $response = $this->getJson("/api/laracord-chat/sessions/{$session->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'session' => [
                        'id' => $session->id,
                        'customer_name' => 'John Doe',
                        'customer_email' => 'john@example.com',
                        'status' => 'active',
                    ],
                ])
                ->assertJsonStructure([
                    'status',
                    'session' => [
                        'id',
                        'customer_name',
                        'customer_email',
                        'status',
                        'created_at',
                        'messages' => [
                            '*' => [
                                'id',
                                'sender_type',
                                'sender_name',
                                'message',
                                'created_at',
                            ]
                        ]
                    ]
                ]);

        $this->assertCount(2, $response->json('session.messages'));
    }

    /** @test */
    public function it_returns_404_for_non_existent_session()
    {
        $response = $this->getJson('/api/laracord-chat/sessions/non-existent-id');

        $response->assertStatus(404)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Session not found',
                ]);
    }

    /** @test */
    public function it_can_get_session_messages_only()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $session->messages()->create([
            'sender_type' => 'customer',
            'message' => 'Message 1',
        ]);

        $session->messages()->create([
            'sender_type' => 'agent',
            'message' => 'Message 2',
        ]);

        $response = $this->getJson("/api/laracord-chat/sessions/{$session->id}/messages");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'messages' => [
                        '*' => [
                            'id',
                            'sender_type',
                            'sender_name',
                            'message',
                            'created_at',
                        ]
                    ]
                ]);

        $this->assertCount(2, $response->json('messages'));
    }

    /** @test */
    public function it_can_close_a_session()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
            'discord_channel_id' => 'test_channel_id',
        ]);

        $response = $this->postJson("/api/laracord-chat/sessions/{$session->id}/close");

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Chat session closed',
                ]);

        $this->assertEquals('closed', $session->fresh()->status);
    }

    /** @test */
    public function it_cannot_close_already_closed_session()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'closed',
        ]);

        $response = $this->postJson("/api/laracord-chat/sessions/{$session->id}/close");

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Session not found or already closed',
                ]);
    }

    /** @test */
    public function it_can_get_session_statistics()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $session->messages()->create(['sender_type' => 'customer', 'message' => 'Message 1']);
        $session->messages()->create(['sender_type' => 'agent', 'message' => 'Message 2']);
        $session->messages()->create(['sender_type' => 'customer', 'message' => 'Message 3']);

        $response = $this->getJson("/api/laracord-chat/sessions/{$session->id}/stats");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'stats' => [
                        'total_messages',
                        'customer_messages',
                        'agent_messages',
                        'duration',
                        'status',
                    ]
                ]);

        $stats = $response->json('stats');
        $this->assertEquals(3, $stats['total_messages']);
        $this->assertEquals(2, $stats['customer_messages']);
        $this->assertEquals(1, $stats['agent_messages']);
        $this->assertEquals('active', $stats['status']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
