<?php

namespace ADReece\LaracordLiveChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    use HasUuids, HasFactory;

    protected $table = 'chat_sessions';

    protected $fillable = [
        'customer_name',
        'customer_email',
        'ip_address',
        'user_agent',
        'status',
        'discord_channel_id',
        'started_at',
        'last_activity',
        'closed_at',
        'closure_reason',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'last_activity' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_session_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class, 'chat_session_id')->latest();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    public function markAsWaiting(): void
    {
        $this->update(['status' => 'waiting']);
    }

    protected static function newFactory()
    {
        return \ADReece\LaracordLiveChat\Database\Factories\ChatSessionFactory::new();
    }
}
