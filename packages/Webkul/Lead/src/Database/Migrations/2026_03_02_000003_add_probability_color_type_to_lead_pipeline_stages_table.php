<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Extends the lead_pipeline_stages table with probability (nullable),
     * type enum, and color hex fields as per the additional-integration spec (A3).
     *
     * Note: the `probability` integer column already exists from the original
     * table creation (default 0). Here we add `type` and `color`, then alter
     * `probability` to be nullable so it matches the spec intent.
     */
    public function up(): void
    {
        Schema::table('lead_pipeline_stages', function (Blueprint $table) {
            $table->enum('type', ['open', 'won', 'lost'])->nullable()->after('sort_order');
            $table->string('color', 7)->nullable()->after('type');
        });

        // Make the existing probability column nullable.
        // doctrine/dbal is available in this project (->change() is used elsewhere).
        Schema::table('lead_pipeline_stages', function (Blueprint $table) {
            $table->integer('probability')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_pipeline_stages', function (Blueprint $table) {
            $table->dropColumn(['type', 'color']);
        });

        Schema::table('lead_pipeline_stages', function (Blueprint $table) {
            $table->integer('probability')->nullable(false)->default(0)->change();
        });
    }
};
