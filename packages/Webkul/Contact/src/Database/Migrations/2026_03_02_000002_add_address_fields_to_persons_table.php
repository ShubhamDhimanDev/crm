<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds address and contact fields to the persons table as per
     * the additional-integration spec (A2). All columns are nullable.
     */
    public function up(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->string('phone_alt')->nullable()->after('job_title');
            $table->string('website')->nullable()->after('phone_alt');
            $table->string('city', 80)->nullable()->after('website');
            $table->string('state')->nullable()->after('city');
            $table->string('country')->nullable()->after('state');
            $table->string('pincode')->nullable()->after('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->dropColumn([
                'phone_alt',
                'website',
                'city',
                'state',
                'country',
                'pincode',
            ]);
        });
    }
};
