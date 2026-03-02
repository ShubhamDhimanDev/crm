<?php

namespace Webkul\GoogleAds\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Lead\Repositories\PipelineRepository;
use Webkul\Lead\Repositories\SourceRepository;

class GoogleAdsService
{
    public function __construct(
        protected LeadRepository     $leadRepository,
        protected PersonRepository   $personRepository,
        protected SourceRepository   $sourceRepository,
        protected PipelineRepository $pipelineRepository
    ) {}

    /**
     * Verify the shared-secret header sent by Google.
     *
     * Google sends the secret as a query parameter or request header.
     * We check the `google_key` query param OR the X-Google-Webhook-Secret header.
     */
    public function verifySecret(string $provided): bool
    {
        $secret = config('google_ads.webhook_secret');

        if (empty($secret)) {
            return true; // dev mode — allow all
        }

        return hash_equals($secret, $provided);
    }

    /**
     * Process a single Google Ads lead payload and create a CRM record.
     *
     * Expected payload keys follow Google's lead form column IDs (FULL_NAME, EMAIL, etc.)
     * plus campaign_name, ad_group, gclid from the event metadata.
     */
    public function processLead(array $payload): void
    {
        try {
            $columns = $this->extractColumnData($payload);

            $name    = $columns['FULL_NAME']     ?? 'Google Lead';
            $email   = $columns['EMAIL']         ?? null;
            $phone   = $columns['PHONE_NUMBER']  ?? null;
            $city    = $columns['CITY']          ?? null;
            $country = $columns['COUNTRY']       ?? null;
            $pincode = $columns['POSTAL_CODE']   ?? null;

            $person = $this->findOrCreatePerson($name, $email, $phone, $city, $country, $pincode);

            $source   = $this->sourceRepository->findOneWhere(['name' => config('google_ads.lead_source_name', 'Google Ads')]);
            $pipeline = $this->pipelineRepository->first();

            $leadPayload = [
                'title'              => $name,
                'person_id'          => $person->id,
                'lead_source_id'     => $source?->id,
                'lead_pipeline_id'   => $pipeline?->id,
                'lead_pipeline_stage_id' => $pipeline?->stages->first()?->id,
                'status'             => 1,

                // A1 tracking fields
                'campaign_name'      => $payload['campaign_name'] ?? null,

                // B10 Google-specific
                'ad_group'           => $payload['adgroup_name']  ?? $payload['ad_group'] ?? null,
                'gclid'              => $payload['gclid']         ?? null,
            ];

            $lead = $this->leadRepository->create($leadPayload);

            Event::dispatch('lead.create.after', $lead);

            Log::info('[GoogleAds] Lead created.', ['lead_id' => $lead->id, 'name' => $name]);

        } catch (\Throwable $e) {
            Log::error('[GoogleAds] Failed to process lead.', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Extract flat column_data from Google's lead payload into a key-value map.
     * Google sends column_data as an array of { column_id, string_value }.
     */
    protected function extractColumnData(array $payload): array
    {
        $map = [];

        foreach ($payload['column_data'] ?? [] as $col) {
            $map[$col['column_id'] ?? ''] = $col['string_value'] ?? null;
        }

        // Also accept direct top-level keys for convenience
        foreach (['FULL_NAME', 'EMAIL', 'PHONE_NUMBER', 'CITY', 'COUNTRY', 'POSTAL_CODE'] as $key) {
            if (isset($payload[$key]) && ! isset($map[$key])) {
                $map[$key] = $payload[$key];
            }
        }

        return $map;
    }

    /**
     * Find an existing person by email or phone, or create a new one.
     */
    protected function findOrCreatePerson(
        string  $name,
        ?string $email,
        ?string $phone,
        ?string $city,
        ?string $country,
        ?string $pincode
    ): \Webkul\Contact\Models\Person {
        if ($email) {
            $match = DB::table('persons')
                ->whereRaw("JSON_SEARCH(emails, 'one', ?) IS NOT NULL", [$email])
                ->first();

            if ($match) {
                return $this->personRepository->find($match->id);
            }
        }

        if ($phone) {
            $match = DB::table('persons')
                ->whereRaw("JSON_SEARCH(contact_numbers, 'one', ?) IS NOT NULL", [$phone])
                ->first();

            if ($match) {
                return $this->personRepository->find($match->id);
            }
        }

        return $this->personRepository->create([
            'name'            => $name,
            'emails'          => $email ? [['label' => 'work', 'value' => $email]] : [],
            'contact_numbers' => $phone ? [['label' => 'work', 'value' => $phone]] : [],
            'city'            => $city,
            'country'         => $country,
            'pincode'         => $pincode,
        ]);
    }
}
