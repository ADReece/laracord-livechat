<?php

namespace ADReece\LaracordLiveChat\Database\Factories;

use ADReece\LaracordLiveChat\Models\ChatMessage;
use ADReece\LaracordLiveChat\Models\ChatSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    public function definition()
    {
        return [
            'chat_session_id' => ChatSession::factory(),
            'content' => $this->faker->paragraph(),
            'sender_type' => $this->faker->randomElement(['customer', 'agent']),
            'sender_name' => $this->faker->optional()->name(),
            'discord_message_id' => $this->faker->optional()->numerify('############'),
            'created_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    public function fromCustomer()
    {
        return $this->state(function (array $attributes) {
            return [
                'sender_type' => 'customer',
                'sender_name' => $this->faker->name(),
                'discord_message_id' => null,
            ];
        });
    }

    public function fromAgent()
    {
        return $this->state(function (array $attributes) {
            return [
                'sender_type' => 'agent',
                'sender_name' => $this->faker->name(),
                'discord_message_id' => $this->faker->numerify('############'),
            ];
        });
    }

    public function withDiscordId()
    {
        return $this->state(function (array $attributes) {
            return [
                'discord_message_id' => $this->faker->numerify('############'),
            ];
        });
    }

    public function withoutDiscordId()
    {
        return $this->state(function (array $attributes) {
            return [
                'discord_message_id' => null,
            ];
        });
    }

    public function recent()
    {
        return $this->state(function (array $attributes) {
            return [
                'created_at' => $this->faker->dateTimeBetween('-5 minutes', 'now'),
            ];
        });
    }

    public function old()
    {
        return $this->state(function (array $attributes) {
            return [
                'created_at' => $this->faker->dateTimeBetween('-2 hours', '-1 hour'),
            ];
        });
    }
}
