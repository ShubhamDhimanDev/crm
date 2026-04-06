<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds new fields to the leads table as per the additional-integration spec (A1).
     * All columns are nullable to avoid breaking existing data.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->enum('priority', ['hot', 'warm', 'cold'])->nullable()->after('status');
            $table->unsignedTinyInteger('lead_score')->nullable()->after('priority');
            $table->string('industry')->nullable()->after('lead_score');
            $table->string('campaign_name')->nullable()->after('industry');
            $table->string('ad_name')->nullable()->after('campaign_name');
            $table->string('form_name')->nullable()->after('ad_name');
            $table->datetime('followup_at')->nullable()->after('form_name');
            $table->datetime('last_contacted_at')->nullable()->after('followup_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'priority',
                'lead_score',
                'industry',
                'campaign_name',
                'ad_name',
                'form_name',
                'followup_at',
                'last_contacted_at',
            ]);
        });
    }
};
