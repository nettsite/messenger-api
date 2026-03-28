<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messenger_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('body');
            $table->string('url')->nullable();
            $table->string('sender_type')->nullable();
            $table->char('sender_id', 36)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['sender_type', 'sender_id']);
        });
    }
};
