<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('gclid')->nullable()->after('source_created_at')
                ->comment('Google Click ID — never editable');
            $table->string('ad_group')->nullable()->after('gclid')
                ->comment('Google Ads Group Name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['gclid', 'ad_group']);
        });
    }
};
