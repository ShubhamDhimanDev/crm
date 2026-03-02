<?php

namespace Webkul\Slack\Services;

class LeadParser
{
    /**
     * Maps canonical field names to accepted user-facing aliases.
     * Keys are the canonical names used internally.
     */
    protected array $fieldMap = [
        'name'               => ['name', 'contact', 'person', 'contact name', 'full name'],
        'phone'              => ['phone', 'mobile', 'tel', 'telephone', 'cell', 'contact number'],
        'email'              => ['email', 'mail', 'e-mail'],
        'company'            => ['company', 'org', 'organization', 'organisation', 'business', 'firm', 'account'],
        'value'              => ['value', 'deal value', 'amount', 'budget', 'price', 'deal size', 'revenue'],
        'notes'              => ['notes', 'note', 'description', 'desc', 'info', 'details', 'message', 'comment'],
        'title'              => ['title', 'lead title', 'subject', 'opportunity'],
        'lost_reason'        => ['lost reason', 'reason', 'loss reason', 'why lost'],
        'expected_close_date'=> ['close date', 'expected close', 'expected close date', 'closing date', 'close by', 'deadline'],
    ];

    /**
     * Determine whether the given Slack message text represents a lead submission.
     * The message must start with "lead:" (case-insensitive).
     */
    public function isLeadMessage(string $text): bool
    {
        return (bool) preg_match('/^\s*lead\s*:/i', $text);
    }

    /**
     * Parse a lead-formatted Slack message into a key→value array.
     *
     * Supported format:
     *   lead:
     *   Name: John Smith
     *   Phone: +1 555 123 4567
     *   Email: john@example.com
     *   Company: Acme Corp
     *   Value: 5000
     *   Notes: Interested in enterprise plan
     *
     * @return array<string, string>  Canonical field names as keys
     */
    public function parse(string $text): array
    {
        // Strip the "lead:" trigger prefix (handle optional newline after it)
        $body = preg_replace('/^\s*lead\s*:/i', '', $text, 1);

        $lines  = explode("\n", $body);
        $parsed = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // Split on the FIRST colon only
            $colonPos = strpos($line, ':');

            if ($colonPos === false) {
                continue;
            }

            $rawKey = strtolower(trim(substr($line, 0, $colonPos)));
            $value  = trim(substr($line, $colonPos + 1));

            if (empty($value)) {
                continue;
            }

            // Strip Slack's auto-link formatting:
            //   <mailto:foo@bar.com|foo@bar.com>  →  foo@bar.com
            //   <tel:+15551234567|+1 555 123 4567> →  +1 555 123 4567
            //   <https://example.com|example.com>  →  example.com
            $value = $this->stripSlackLinks($value);

            if (empty($value)) {
                continue;
            }

            $canonical = $this->mapField($rawKey);

            if ($canonical !== null) {
                $parsed[$canonical] = $value;
            }
        }

        return $parsed;
    }

    /**
     * Convert parsed field array into the data structure expected by LeadRepository::create().
     */
    public function toLeadData(array $parsed): array
    {
        // Build lead title
        $title = $parsed['title']
            ?? (isset($parsed['name']) ? "Lead from {$parsed['name']}" : 'New Lead from Slack');

        $data = [
            'title'               => $title,
            'description'         => $parsed['notes'] ?? null,
            'lead_value'          => isset($parsed['value'])
                ? (float) preg_replace('/[^0-9.]/', '', $parsed['value'])
                : null,
            'lost_reason'         => $parsed['lost_reason'] ?? null,
            'expected_close_date' => isset($parsed['expected_close_date'])
                ? $this->parseDate($parsed['expected_close_date'])
                : null,
            'status'              => 1,   // 1 = active/open; required for lead to appear in pipeline views
            'entity_type'         => 'leads',
        ];

        // Build contact/person sub-array if any contact info was provided
        $hasContact = isset($parsed['name'])
            || isset($parsed['email'])
            || isset($parsed['phone'])
            || isset($parsed['company']);

        if ($hasContact) {
            $emails = [];

            if (! empty($parsed['email'])) {
                $emails[] = ['value' => $parsed['email'], 'label' => 'work'];
            }

            $phones = [];

            if (! empty($parsed['phone'])) {
                $phones[] = ['value' => $parsed['phone'], 'label' => 'work'];
            }

            $data['person'] = [
                'entity_type'     => 'persons',
                'name'            => $parsed['name'] ?? 'Unknown',
                'emails'          => $emails,
                'contact_numbers' => $phones,
            ];

            if (! empty($parsed['company'])) {
                $data['person']['organization_name'] = $parsed['company'];
            }
        }

        return $data;
    }

    /**
     * Attempt to parse a human-entered date string into Y-m-d format.
     * Accepts formats like "2026-03-31", "31/03/2026", "March 31 2026", "31 Mar 26" etc.
     */
    protected function parseDate(string $value): ?string
    {
        try {
            $ts = strtotime($value);

            return $ts !== false ? date('Y-m-d', $ts) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Strip Slack's automatic link-formatting from a value string.
     *
     * Slack wraps recognised values in angle brackets, e.g.:
     *   <mailto:foo@bar.com|foo@bar.com>   → foo@bar.com
     *   <tel:+15551234|+1 555 123 4>       → +1 555 123 4   (display text)
     *   <https://example.com|Example>      → Example        (display text)
     *   <https://example.com>              → https://example.com
     *
     * If no Slack formatting is found the original value is returned unchanged.
     */
    protected function stripSlackLinks(string $value): string
    {
        // Pattern: <scheme:something|display_text> — return display_text
        // Pattern: <scheme:something>              — return the part after the colon(s)
        return preg_replace_callback('/<([^>]+)>/', function ($matches) {
            $inner = $matches[1];

            // Has a pipe — return the human-readable display text on the right
            if (str_contains($inner, '|')) {
                return trim(explode('|', $inner, 2)[1]);
            }

            // mailto:foo@bar.com → foo@bar.com
            if (str_starts_with($inner, 'mailto:')) {
                return substr($inner, 7);
            }

            // tel:+15551234 → +15551234
            if (str_starts_with($inner, 'tel:')) {
                return substr($inner, 4);
            }

            // Bare URL — return as-is
            return $inner;
        }, $value) ?? $value;
    }

    /**
     * Map a raw user-typed field label to its canonical name.
     * Returns null if no match is found.
     */
    protected function mapField(string $key): ?string
    {
        foreach ($this->fieldMap as $canonical => $aliases) {
            if (in_array($key, $aliases, true)) {
                return $canonical;
            }
        }

        return null;
    }
}
