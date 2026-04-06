<?php

namespace Webkul\Installer\Database\Seeders\Lead;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SourceSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        DB::table('lead_sources')->delete();

        $now = Carbon::now();

        $defaultLocale = $parameters['locale'] ?? config('app.locale');

        DB::table('lead_sources')->insert([
            [
                'id'         => 1,
                'name'       => trans('installer::app.seeders.lead.source.email', [], $defaultLocale),
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'id'         => 2,
                'name'       => trans('installer::app.seeders.lead.source.web', [], $defaultLocale),
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'id'         => 3,
                'name'       => trans('installer::app.seeders.lead.source.web-form', [], $defaultLocale),
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'id'         => 4,
                'name'       => trans('installer::app.seeders.lead.source.phone', [], $defaultLocale),
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'id'         => 5,
                'name'       => trans('installer::app.seeders.lead.source.direct', [], $defaultLocale),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // A4 — additional required sources
            [
                'id'         => 6,
                'name'       => trans('installer::app.seeders.lead.source.manual-entry', [], $defaultLocale),
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'id'         => 7,
                'name'       => trans('installer::app.seeders.lead.source.website', [], $defaultLocale),
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'id'         => 8,
                'name'       => trans('installer::app.seeders.lead.source.slack', [], $defaultLocale),
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'id'         => 9,
                'name'       => trans('installer::app.seeders.lead.source.meta-ads', [], $defaultLocale),
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'id'         => 10,
                'name'       => trans('installer::app.seeders.lead.source.google-ads', [], $defaultLocale),
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'id'         => 11,
                'name'       => trans('installer::app.seeders.lead.source.referral', [], $defaultLocale),
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'id'         => 12,
                'name'       => trans('installer::app.seeders.lead.source.cold-call', [], $defaultLocale),
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'id'         => 13,
                'name'       => trans('installer::app.seeders.lead.source.exhibition', [], $defaultLocale),
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'id'         => 14,
                'name'       => trans('installer::app.seeders.lead.source.other', [], $defaultLocale),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
