<?php

namespace ADReece\LaracordLiveChat\Database\Factories;

use ADReece\LaracordLiveChat\Models\ChatMessage;
use ADReece\LaracordLiveChat\Models\ChatSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    public function definition(): array
    {
        return [
            'chat_session_id' => ChatSession::factory(),
            'sender_type' => $this->faker->randomElement(['customer', 'agent']),
            'sender_name' => $this->faker->name(),
            'content' => $this->faker->sentence(),
            'discord_message_id' => $this->faker->optional()->numerify('####################'),
            'is_read' => $this->faker->boolean(),
            'metadata' => null,
        ];
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_type' => 'customer',
        ]);
    }

    public function agent(): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_type' => 'agent',
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
        ]);
    }
}
