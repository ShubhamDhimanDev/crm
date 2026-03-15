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
use Webkul\Lead\Repositories\TypeRepository;

class GoogleAdsService
{
    public function __construct(
        protected LeadRepository     $leadRepository,
        protected PersonRepository   $personRepository,
        protected SourceRepository   $sourceRepository,
        protected TypeRepository     $typeRepository,
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
     * Payload follows Google's Lead Form webhook schema:
     * - user_column_data: array of { column_id, column_name, string_value }
     * - gcl_id, campaign_id, adgroup_id, form_id from the event metadata.
     */
    public function processLead(array $payload): void
    {
        try {
            $columns = $this->extractColumnData($payload);

            $name        = $columns['FULL_NAME']    ?? 'Google Lead';
            $email       = $columns['EMAIL']        ?? null;
            $phone       = $columns['PHONE_NUMBER'] ?? null;
            $city        = $columns['CITY']         ?? null;
            $country     = $columns['COUNTRY']      ?? null;
            $pincode     = $columns['POSTAL_CODE']  ?? null;
            $state       = $columns['REGION']       ?? null;
            $companyName = $columns['COMPANY_NAME'] ?? null;

            $person = $this->findOrCreatePerson($name, $email, $phone, $city, $state, $country, $pincode);

            $source   = $this->sourceRepository->findOneWhere(['name' => config('google_ads.lead_source_name', 'Google')]);
            $type     = $this->typeRepository->findOneWhere(['name' => config('google_ads.lead_type_name', 'New Business')]);
            $pipeline = $this->pipelineRepository->first();

            $leadPayload = [
                'title'              => $companyName ?? $name,
                'entity_type'        => 'leads',
                'person_id'          => $person->id,
                'lead_source_id'     => $source?->id,
                'lead_type_id'       => $type?->id,
                'lead_pipeline_id'   => $pipeline?->id,
                'lead_pipeline_stage_id' => $pipeline?->stages->first()?->id,
                'status'             => 1,

                // A1 tracking fields
                'campaign_name'      => isset($payload['campaign_id']) ? (string) $payload['campaign_id'] : null,
                'form_name'          => isset($payload['form_id'])     ? (string) $payload['form_id']     : null,

                // B10 Google-specific
                'ad_group'           => isset($payload['adgroup_id'])  ? (string) $payload['adgroup_id']  : null,
                'gclid'              => $payload['gcl_id']             ?? null,
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
     * Extract flat user_column_data from Google's lead payload into a key-value map.
     * Google sends user_column_data as an array of { column_id, column_name, string_value }.
     */
    protected function extractColumnData(array $payload): array
    {
        $map = [];

        foreach ($payload['user_column_data'] ?? [] as $col) {
            $map[$col['column_id'] ?? ''] = $col['string_value'] ?? null;
        }

        // Also accept direct top-level keys for convenience
        foreach (['FULL_NAME', 'EMAIL', 'PHONE_NUMBER', 'CITY', 'COUNTRY', 'POSTAL_CODE', 'REGION', 'COMPANY_NAME'] as $key) {
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
        ?string $state,
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
            'state'           => $state,
            'country'         => $country,
            'pincode'         => $pincode,
        ]);
    }
}
