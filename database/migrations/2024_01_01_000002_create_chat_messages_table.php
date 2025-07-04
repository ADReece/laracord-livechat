<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id');
            $table->enum('sender_type', ['customer', 'agent']);
            $table->string('sender_name')->nullable();
            $table->text('message');
            $table->string('discord_message_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('chat_sessions')->onDelete('cascade');
            $table->index(['session_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_messages');
    }
};
