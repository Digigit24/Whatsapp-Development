<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Event reminder configurations (admin configurable)
        Schema::create('event_reminder_configs', function (Blueprint $table) {
            $table->id('_id');
            $table->uuid('_uid')->unique();
            $table->string('event_type', 100)->index(); // followup_appointment, payment_due, etc.
            $table->integer('reminder_offset_minutes'); // negative = before, positive = after
            $table->string('template_name', 255);
            $table->string('template_language', 10)->default('en');
            $table->unsignedBigInteger('vendors__id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->json('__data')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'vendors__id', 'is_active']);
        });

        // Scheduled events (main events like appointments)
        Schema::create('scheduled_events', function (Blueprint $table) {
            $table->id('_id');
            $table->uuid('_uid')->unique();
            $table->string('event_type', 100)->index();
            $table->string('contact_phone', 20);
            $table->string('contact_name', 255)->nullable();
            $table->unsignedBigInteger('contacts__id')->nullable()->index();
            $table->unsignedBigInteger('vendors__id')->index();
            $table->timestamp('event_at');
            $table->string('timezone', 50)->default('UTC');
            $table->tinyInteger('status')->default(1)->index(); // 1=active, 2=cancelled, 3=completed
            $table->json('__data')->nullable(); // metadata like doctor_name, clinic, etc.
            $table->timestamps();

            $table->index(['event_at', 'status']);
            $table->index(['vendors__id', 'event_type']);
        });

        // Scheduled messages (auto-generated from events)
        Schema::create('scheduled_messages', function (Blueprint $table) {
            $table->id('_id');
            $table->uuid('_uid')->unique();
            $table->unsignedBigInteger('scheduled_events__id')->nullable()->index();
            $table->string('message_type', 20)->default('template'); // template, text, media, interactive
            $table->string('contact_phone', 20);
            $table->string('contact_name', 255)->nullable();
            $table->unsignedBigInteger('contacts__id')->nullable()->index();
            $table->unsignedBigInteger('vendors__id')->index();
            $table->timestamp('send_at')->index();
            $table->string('timezone', 50)->default('UTC');
            $table->tinyInteger('status')->default(1)->index(); // 1=scheduled, 2=sent, 3=failed, 4=cancelled
            $table->integer('retries')->default(0);
            $table->string('template_name', 255)->nullable();
            $table->string('template_language', 10)->nullable();
            $table->json('__data')->nullable(); // template_params, message_content, error_response, etc.
            $table->timestamps();

            $table->index(['send_at', 'status']);
            $table->index(['vendors__id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_messages');
        Schema::dropIfExists('scheduled_events');
        Schema::dropIfExists('event_reminder_configs');
    }
};
