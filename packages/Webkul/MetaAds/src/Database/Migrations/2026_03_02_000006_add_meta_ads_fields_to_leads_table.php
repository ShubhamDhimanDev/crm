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
            $table->string('meta_ad_id')->nullable()->after('form_name');
            $table->string('meta_adset_id')->nullable()->after('meta_ad_id');
            $table->string('meta_campaign_id')->nullable()->after('meta_adset_id');
            $table->string('meta_form_id')->nullable()->after('meta_campaign_id');
            $table->string('meta_page_id')->nullable()->after('meta_form_id');
            $table->string('platform')->nullable()->after('meta_page_id')->comment('fb or ig');
            $table->dateTime('source_created_at')->nullable()->after('platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'meta_ad_id',
                'meta_adset_id',
                'meta_campaign_id',
                'meta_form_id',
                'meta_page_id',
                'platform',
                'source_created_at',
            ]);
        });
    }
};
