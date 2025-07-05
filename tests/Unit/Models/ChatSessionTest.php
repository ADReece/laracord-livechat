<?php

namespace ADReece\LaracordLiveChat\Tests\Unit\Models;

use ADReece\LaracordLiveChat\Tests\TestCase;
use ADReece\LaracordLiveChat\Models\ChatSession;
use ADReece\LaracordLiveChat\Models\ChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatSessionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_chat_session()
    {
        $session = ChatSession::create([
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(ChatSession::class, $session);
        $this->assertEquals('John Doe', $session->customer_name);
        $this->assertEquals('john@example.com', $session->customer_email);
        $this->assertEquals('active', $session->status);
        $this->assertTrue($session->isActive());
    }

    /** @test */
    public function it_has_uuid_primary_key()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $this->assertIsString($session->id);
        $this->assertEquals(36, strlen($session->id)); // UUID length
    }

    /** @test */
    public function it_can_have_messages()
    {
        $session = ChatSession::create([
            'customer_name' => 'John Doe',
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $message = $session->messages()->create([
            'sender_type' => 'customer',
            'sender_name' => 'John Doe',
            'message' => 'Hello, I need help!',
        ]);

        $this->assertInstanceOf(ChatMessage::class, $message);
        $this->assertEquals(1, $session->messages()->count());
        $this->assertEquals('Hello, I need help!', $session->messages->first()->message);
    }

    /** @test */
    public function it_can_be_closed()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $this->assertTrue($session->isActive());

        $session->close();

        $this->assertFalse($session->isActive());
        $this->assertEquals('closed', $session->status);
    }

    /** @test */
    public function it_can_be_marked_as_waiting()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        $session->markAsWaiting();

        $this->assertEquals('waiting', $session->status);
    }

    /** @test */
    public function it_has_latest_message_relationship()
    {
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
        ]);

        // Create multiple messages
        $session->messages()->create([
            'sender_type' => 'customer',
            'message' => 'First message',
        ]);

        sleep(1); // Ensure different timestamps

        $latestMessage = $session->messages()->create([
            'sender_type' => 'agent',
            'message' => 'Latest message',
        ]);

        $this->assertEquals('Latest message', $session->latestMessage->message);
    }

    /** @test */
    public function it_casts_metadata_to_array()
    {
        $metadata = ['browser' => 'Chrome', 'os' => 'Windows'];
        
        $session = ChatSession::create([
            'ip_address' => '192.168.1.1',
            'status' => 'active',
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($session->metadata);
        $this->assertEquals($metadata, $session->metadata);
    }
}
