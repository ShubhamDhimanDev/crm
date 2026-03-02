<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the slack_channels table for multi-channel configuration (A7).
     * Each row represents a Slack channel the CRM can post to, with per-channel
     * notification toggles.
     */
    public function up(): void
    {
        Schema::create('slack_channels', function (Blueprint $table) {
            $table->id();
            $table->string('channel_name');
            $table->string('channel_id')->nullable()->comment('Slack channel ID (C0123ABCD)');
            $table->boolean('notify_on_new_lead')->default(true);
            $table->boolean('notify_on_stage_change')->default(true);
            $table->boolean('notify_on_assignment')->default(false);
            $table->text('webhook_url')->nullable()->comment('Incoming webhook URL (alternative to bot token)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slack_channels');
    }
};
