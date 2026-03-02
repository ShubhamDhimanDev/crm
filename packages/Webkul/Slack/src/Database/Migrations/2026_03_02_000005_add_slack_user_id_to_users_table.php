<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the slack_user_id column to the users table (A8).
     * Used to map a CRM user to their Slack account for direct message notifications.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slack_user_id')->nullable()->after('password')
                ->comment('Slack member ID (Uxxxxxxxx) for direct message notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('slack_user_id');
        });
    }
};
