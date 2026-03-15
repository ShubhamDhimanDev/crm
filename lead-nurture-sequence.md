# Lead Nurture Sequence — Implementation Plan

## Overview

When a lead is created anywhere in the CRM (admin form, web form, Slack command, Meta Ads, Google Ads),
automatically send a 3-step nurture sequence to the lead's contact via **Email** and **WhatsApp**:

| Step | Timing | Content |
|------|--------|---------|
| 1 | Immediately | Thank-you message |
| 2 | 1–2 minutes later | Company profile |
| 3 | 10–20 minutes later | Custom link (configurable in CRM settings) |

All work is handled inside the existing **`Webkul\WhatsApp`** package to keep things self-contained.

---

## Architecture

```
lead.create.after  ──▶  LeadNurtureListener
                              │
                              ├─ SendLeadWelcomeJob::dispatch($lead)               [delay: 0s]
                              ├─ SendLeadCompanyProfileJob::dispatch($lead)        [delay: 60–120s]
                              └─ SendLeadCustomLinkJob::dispatch($lead)            [delay: 600–1200s]
                                         │
                              Each Job:  ├─ Mail::to($email)->send(new LeadXxxMail($lead))
                                         └─ WhatsAppService::sendMessage($phone, $text)
```

---

## Prerequisites

### 1. Switch Queue Driver from `sync` to `database`

`sync` executes everything inline — delayed jobs will **not** work with it.

**Steps:**
1. Change `.env`:
   ```
   QUEUE_CONNECTION=database
   ```
2. Run migrations to create the jobs table:
   ```
   php artisan queue:table
   php artisan migrate
   ```
3. Start the worker (keep this running in production via Supervisor or a service):
   ```
   php artisan queue:work --sleep=3 --tries=3
   ```

---

## Files to Create

All new files live under `packages/Webkul/WhatsApp/src/`.

### Package Structure After Implementation

```
packages/Webkul/WhatsApp/src/
├── Config/
│   └── whatsapp.php               ← add nurture settings keys
├── Http/
│   └── Controllers/
│       └── WhatsAppController.php ← add saveNurtureSettings() action
├── Jobs/
│   ├── SendLeadWelcomeJob.php      ← NEW
│   ├── SendLeadCompanyProfileJob.php ← NEW
│   └── SendLeadCustomLinkJob.php   ← NEW
├── Listeners/
│   └── LeadNurtureListener.php    ← NEW
├── Mails/
│   ├── LeadThankYouMail.php       ← NEW
│   ├── LeadCompanyProfileMail.php ← NEW
│   └── LeadCustomLinkMail.php     ← NEW
├── Providers/
│   ├── WhatsAppServiceProvider.php ← register EventServiceProvider
│   └── EventServiceProvider.php   ← NEW  (maps lead.create.after → listener)
├── Resources/
│   └── views/
│       ├── settings.blade.php     ← extend with nurture config section
│       ├── lead-send-button.blade.php
│       └── emails/
│           ├── thank-you.blade.php     ← NEW
│           ├── company-profile.blade.php ← NEW
│           └── custom-link.blade.php   ← NEW
├── Routes/
│   └── web.php
└── Services/
    └── WhatsAppService.php        ← add resolveLeadContact() helper
```

---

## Step-by-Step Implementation

### Step 1 — Queue Setup ✅

- [x] Changed `QUEUE_CONNECTION=database` in `.env`
- [ ] Run `php artisan queue:table && php artisan migrate` *(requires DB running — run manually)*
- [ ] Confirm `jobs` table exists in DB *(run after migrate)*

---

### Step 2 — Config (`Config/whatsapp.php`) ✅

Add nurture sequence keys so they are accessible via `config('whatsapp.nurture.*')`:

```php
'nurture' => [
    'enabled'                 => env('WHATSAPP_NURTURE_ENABLED', true),
    'welcome_enabled'         => env('WHATSAPP_NURTURE_WELCOME_ENABLED', true),
    'company_profile_enabled' => env('WHATSAPP_NURTURE_COMPANY_PROFILE_ENABLED', true),
    'custom_link_enabled'     => env('WHATSAPP_NURTURE_CUSTOM_LINK_ENABLED', true),

    // Delay windows (seconds)
    'company_profile_delay_min' => env('WHATSAPP_NURTURE_PROFILE_DELAY_MIN', 60),
    'company_profile_delay_max' => env('WHATSAPP_NURTURE_PROFILE_DELAY_MAX', 120),
    'custom_link_delay_min'     => env('WHATSAPP_NURTURE_LINK_DELAY_MIN', 600),
    'custom_link_delay_max'     => env('WHATSAPP_NURTURE_LINK_DELAY_MAX', 1200),

    // Configurable content (saved to .env via settings page)
    'custom_link_url'         => env('WHATSAPP_NURTURE_CUSTOM_LINK_URL', ''),
    'company_profile_text'    => env('WHATSAPP_NURTURE_COMPANY_PROFILE_TEXT', ''),
    'thank_you_text'          => env('WHATSAPP_NURTURE_THANK_YOU_TEXT', ''),
],
```

---

### Step 3 — Contact Resolution Helper (`Services/WhatsAppService.php`) ✅

Add a `resolveLeadContact(Lead $lead): array` method that returns:
```php
[
    'email' => 'contact@example.com',   // null if not found
    'phone' => '+919876543210',          // null if not found
    'name'  => 'John Doe',
]
```

Logic:
- `$lead->person->emails[0]['value']` for email
- `$lead->person->contact_numbers[0]['value']` for phone
- Falls back to `$lead->person->name` or `$lead->title`

---

### Step 4 — Three Mailable Classes (`Mails/`) ✅

Each mailable is simple and uses a Blade view.

**`LeadThankYouMail`**
- Subject: `"Thank you for your interest, {name}!"`
- View: `whatsapp::emails.thank-you`
- Passes: `$lead`, `$name`

**`LeadCompanyProfileMail`**
- Subject: `"About {company name} — Here's what we do"`
- View: `whatsapp::emails.company-profile`
- Passes: `$lead`, `$profileText` (from config)

**`LeadCustomLinkMail`**
- Subject: `"One more thing — we'd love to know more about you"`
- View: `whatsapp::emails.custom-link`
- Passes: `$lead`, `$linkUrl` (from config)

---

### Step 5 — Email Blade Views (`Resources/views/emails/`) ✅

Three simple HTML email templates:

**`thank-you.blade.php`** — Greeting + thank-you message (config `nurture.thank_you_text` or default text)

**`company-profile.blade.php`** — Company profile body from config `nurture.company_profile_text`

**`custom-link.blade.php`** — A call-to-action button linking to `nurture.custom_link_url`

All three share a simple, clean layout (inline CSS, dark-mode safe).

---

### Step 6 — Three Queueable Jobs (`Jobs/`) ✅

Each job:
- Implements `ShouldQueue`
- Uses `SerializesModels` + `InteractsWithQueue`
- Accepts `$lead` in constructor
- In `handle()`:
  1. Resolves contact (email + phone) via `WhatsAppService::resolveLeadContact()`
  2. Sends email if email is present and step is enabled
  3. Sends WhatsApp if phone is present and step is enabled
  4. Logs activity via `ActivityRepository`

**`SendLeadWelcomeJob`** — no delay, dispatched immediately

**`SendLeadCompanyProfileJob`** — dispatch with `->delay(rand(min, max))`

**`SendLeadCustomLinkJob`** — dispatch with `->delay(rand(min, max))`

---

### Step 7 — Listener (`Listeners/LeadNurtureListener.php`) ✅

```php
public function handle(mixed $lead): void
{
    if (! config('whatsapp.nurture.enabled')) return;

    SendLeadWelcomeJob::dispatch($lead);

    $profileDelay = rand(
        config('whatsapp.nurture.company_profile_delay_min'),
        config('whatsapp.nurture.company_profile_delay_max')
    );
    SendLeadCompanyProfileJob::dispatch($lead)->delay($profileDelay);

    $linkDelay = rand(
        config('whatsapp.nurture.custom_link_delay_min'),
        config('whatsapp.nurture.custom_link_delay_max')
    );
    SendLeadCustomLinkJob::dispatch($lead)->delay($linkDelay);
}
```

---

### Step 8 — Event Service Provider (`Providers/EventServiceProvider.php`) ✅

New file, mirrors the Slack package pattern:

```php
protected $listen = [
    'lead.create.after' => [
        'Webkul\WhatsApp\Listeners\LeadNurtureListener@handle',
    ],
];
```

Register it in `WhatsAppServiceProvider::register()`:
```php
$this->app->register(EventServiceProvider::class);
```

---

### Step 9 — Settings Page Extension (`Resources/views/settings.blade.php`) ✅

Add a **"Lead Nurture Sequence"** section to the existing settings page with fields:

| Field | Env Key | Type |
|-------|---------|------|
| Enable nurture sequence | `WHATSAPP_NURTURE_ENABLED` | Toggle |
| Thank-you message text | `WHATSAPP_NURTURE_THANK_YOU_TEXT` | Textarea |
| Enable company profile step | `WHATSAPP_NURTURE_COMPANY_PROFILE_ENABLED` | Toggle |
| Company profile content | `WHATSAPP_NURTURE_COMPANY_PROFILE_TEXT` | Textarea |
| Enable custom link step | `WHATSAPP_NURTURE_CUSTOM_LINK_ENABLED` | Toggle |
| Custom link URL | `WHATSAPP_NURTURE_CUSTOM_LINK_URL` | Text (URL) |

The existing `saveSettings()` controller method already writes to `.env` — extend its `$envMap` array to include these new keys.

---

### Step 10 — Activity Logging (inside each Job) ✅

Each job logs a `whatsapp` + email activity on the lead, same pattern already used by the outbound send:

```php
$this->activityRepository->create([
    'type'     => 'whatsapp',   // or 'email'
    'title'    => 'Nurture — Step 1: Thank-you',
    'comment'  => $messageText,
    'is_done'  => 1,
    'lead_id'  => $lead->id,
    'additional' => json_encode(['direction' => 'outbound', 'channel' => 'whatsapp']),
]);
```

---

## Environment Variables to Add to `.env`

```dotenv
# Lead Nurture Sequence
WHATSAPP_NURTURE_ENABLED=true
WHATSAPP_NURTURE_WELCOME_ENABLED=true
WHATSAPP_NURTURE_COMPANY_PROFILE_ENABLED=true
WHATSAPP_NURTURE_CUSTOM_LINK_ENABLED=true

WHATSAPP_NURTURE_PROFILE_DELAY_MIN=60
WHATSAPP_NURTURE_PROFILE_DELAY_MAX=120
WHATSAPP_NURTURE_LINK_DELAY_MIN=600
WHATSAPP_NURTURE_LINK_DELAY_MAX=1200

WHATSAPP_NURTURE_THANK_YOU_TEXT="Thank you for reaching out! We've received your details and will be in touch shortly."
WHATSAPP_NURTURE_COMPANY_PROFILE_TEXT="Here's a brief overview of who we are and what we do..."
WHATSAPP_NURTURE_CUSTOM_LINK_URL=https://your-form-link.com
```

---

## Implementation Order

1. [x] Queue driver setup — `QUEUE_CONNECTION=database` set *(run `php artisan migrate` when DB is up)*
2. [x] Extend `Config/whatsapp.php` with nurture keys
3. [x] Add `resolveLeadContact()` to `WhatsAppService`
4. [x] Create 3 Mailable classes
5. [x] Create 3 email Blade views
6. [x] Create 3 Job classes
7. [x] Create `LeadNurtureListener`
8. [x] Create `EventServiceProvider`
9. [x] Register `EventServiceProvider` in `WhatsAppServiceProvider`
10. [x] Extend settings Blade view + controller `$envMap`
11. [x] Add env vars to `.env`
12. [ ] Test end-to-end (create a lead, watch the jobs queue, check mail + WhatsApp)

---

## Testing Checklist

- [ ] Create a lead via admin form → 3 jobs appear in `jobs` table
- [ ] Worker processes `SendLeadWelcomeJob` immediately → email + WhatsApp sent
- [ ] `SendLeadCompanyProfileJob` fires after ~1 min → email + WhatsApp sent
- [ ] `SendLeadCustomLinkJob` fires after ~10 min → email + WhatsApp sent  
- [ ] All 3 steps logged as activities on the lead
- [ ] Disabling a step in settings → that job skips sending
- [ ] Lead with no email → WhatsApp only (no crash)
- [ ] Lead with no phone → email only (no crash)
- [ ] Lead with no person attached → jobs skip gracefully

---

## Notes

- **No new DB migrations** are needed — the `jobs` table (from `queue:table`) is the only addition.
- **No new packages** need to be installed — `Illuminate\Mail`, `Illuminate\Queue`, and `WhatsAppService` are all already available.
- All configuration lives in `.env` and is editable via the existing WhatsApp settings page — no separate settings module needed.
- The 1–2 min and 10–20 min delays are configurable per env var so they can be tuned without code changes.
