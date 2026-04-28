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
        Schema::table('web_form_attributes', function (Blueprint $table) {
            $table->integer('depends_on_attribute_id')->unsigned()->nullable()->after('is_hidden');
            $table->string('depends_on_value')->nullable()->after('depends_on_attribute_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('web_form_attributes', function (Blueprint $table) {
            $table->dropColumn(['depends_on_attribute_id', 'depends_on_value']);
        });
    }
};
