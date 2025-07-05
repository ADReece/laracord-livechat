<?php

namespace ADReece\LaracordLiveChat\Database\Factories;

use ADReece\LaracordLiveChat\Models\ChatSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatSessionFactory extends Factory
{
    protected $model = ChatSession::class;

    public function definition()
    {
        return [
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->email(),
            'status' => $this->faker->randomElement(['pending', 'active', 'closed']),
            'discord_channel_id' => $this->faker->optional()->numerify('############'),
            'started_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'last_activity' => $this->faker->dateTimeBetween('-30 minutes', 'now'),
            'closed_at' => null,
            'closure_reason' => null,
        ];
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
                'closed_at' => null,
            ];
        });
    }

    public function closed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'closed',
                'closed_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
                'closure_reason' => $this->faker->optional()->randomElement([
                    'Resolved by customer',
                    'Resolved by agent',
                    'Automatically closed due to inactivity',
                    'Customer disconnected',
                ]),
            ];
        });
    }

    public function withDiscordChannel()
    {
        return $this->state(function (array $attributes) {
            return [
                'discord_channel_id' => $this->faker->numerify('############'),
            ];
        });
    }

    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'discord_channel_id' => null,
                'closed_at' => null,
            ];
        });
    }
}
