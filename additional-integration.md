# Additional Integration Plan — Krayin CRM

Version 1.0 · March 2026

This document covers everything that does **not yet exist** in the codebase, split into two categories:

- **Section A — Updates / Changes:** Features that partially exist (schema gaps, missing columns, incomplete Slack pieces) and need to be extended.
- **Section B — New Integrations:** Entirely new modules that need to be built from scratch.

Reference spec: `future-integrations.md`

---

## Table of Contents

**Section A — Updates & Changes**
1. [Lead Module — Missing Schema Fields](#a1-lead-module--missing-schema-fields)
2. [Contact / Person — Missing Schema Fields](#a2-contact--person--missing-schema-fields)
3. [Pipeline Stage — Missing Fields](#a3-pipeline-stage--missing-fields)
4. [Lead Source Seed Values](#a4-lead-source-seed-values)
5. [Activity Types — Formalise Enum](#a5-activity-types--formalise-enum)
6. [Slack — Missing Inbound Feature: /newlead Slash Command](#a6-slack--missing-inbound-feature-newlead-slash-command)
7. [Slack — Multi-Channel & Settings UI](#a7-slack--multi-channel--settings-ui)
8. [Slack — Notify on Lead Assignment (DM)](#a8-slack--notify-on-lead-assignment-dm)

**Section B — New Integrations**
9. [Meta Ads Lead Integration](#b9-meta-ads-lead-integration)
10. [Google Ads Lead Form Integration](#b10-google-ads-lead-form-integration)
11. [WhatsApp Integration](#b11-whatsapp-integration)
12. [AI Call Module](#b12-ai-call-module)
13. [Contract Module](#b13-contract-module)
14. [Team / Sub-Team Management](#b14-team--sub-team-management)
15. [Follow-up Reminder Scheduling](#b15-follow-up-reminder-scheduling)
16. [Per-User Notification Preferences](#b16-per-user-notification-preferences)
17. [Lead Score Engine](#b17-lead-score-engine)
18. [Workspace Settings Additions](#b18-workspace-settings-additions)

---

---

# SECTION A — Updates & Changes

---

## A1. Lead Module — Missing Schema Fields

**What exists:** The `leads` table has core fields (`title`, `lead_value`, `status`, `lead_source_id`, etc.).

**What's missing:** Several fields from the spec are not in the schema and need new migrations.

### Fields to add to the `leads` table

| Column | Type | Notes |
|---|---|---|
| `priority` | `enum('hot','warm','cold')` | Color-coded: Red / Orange / Blue |
| `lead_score` | `tinyint unsigned, nullable` | 0–100, auto-calculated |
| `industry` | `string, nullable` | Dropdown — Real Estate, Tech, Healthcare, etc. |
| `campaign_name` | `string, nullable` | Auto-filled from Meta/Google; editable |
| `ad_name` | `string, nullable` | Auto-filled from Meta/Google; editable |
| `form_name` | `string, nullable` | Name of the lead form submitted |
| `followup_at` | `datetime, nullable` | Scheduled follow-up date-time |
| `last_contacted_at` | `datetime, nullable` | Auto-updated when note/call logged |

### Migration to create

```
packages/Webkul/Lead/src/Database/Migrations/
  2026_03_XX_000000_add_new_fields_to_leads_table.php
```

### Model `$fillable` update

`packages/Webkul/Lead/src/Models/Lead.php` — add all new columns to `$fillable` and relevant `$casts`.

---

## A2. Contact / Person — Missing Schema Fields

**What exists:** `persons` table has `name`, `emails` (JSON), `contact_numbers` (JSON), `job_title`, `organization_id`.

**What's missing:**

| Column | Type | Notes |
|---|---|---|
| `phone_alt` | `string, nullable` | Secondary / alternate phone number |
| `website` | `string, nullable` | Must start with http/https |
| `city` | `string, nullable` | Max 80 chars |
| `state` | `string, nullable` | State / Province |
| `country` | `string, nullable` | ISO 3166-1 country code |
| `pincode` | `string, nullable` | Postal / ZIP code |

### Migration to create

```
packages/Webkul/Contact/src/Database/Migrations/
  2026_03_XX_000000_add_address_fields_to_persons_table.php
```

### Model update

`packages/Webkul/Contact/src/Models/Person.php` — add new columns to `$fillable`.

---

## A3. Pipeline Stage — Missing Fields

**What exists:** `lead_pipeline_stages` has `name`, `code`, `sort_order`.

**What's missing:**

| Column | Type | Notes |
|---|---|---|
| `probability` | `tinyint unsigned, nullable` | 0–100%. Used for weighted pipeline value |
| `type` | `enum('open','won','lost'), nullable` | Won/Lost stages trigger deal reporting |
| `color` | `string(7), nullable` | Hex color for column header badge |

### Migration to create

```
packages/Webkul/Lead/src/Database/Migrations/
  2026_03_XX_000000_add_probability_color_type_to_lead_pipeline_stages_table.php
```

---

## A4. Lead Source Seed Values

**What exists:** `lead_sources` table and seeder exist, but source names are generic.

**What's needed:** Ensure the following source records are seeded:

- Manual Entry
- Website
- Slack ← required by the custom Slack integration
- Meta Ads ← required by Meta integration (Section B9)
- Google Ads ← required by Google integration (Section B10)
- Referral
- Cold Call
- Exhibition
- Other

### Where to update

`packages/Webkul/Lead/src/Database/Seeders/LeadSourceSeeder.php` (or equivalent).

---

## A5. Activity Types — Formalise Enum

**What exists:** `activities.type` is a free-form string. Native UI supports: `call`, `meeting`, `lunch`, `email`, `note`.

**What's needed:** The following types are used in the spec and by planned integrations but are not formally registered in the UI/config:

| Type value | Added by |
|---|---|
| `whatsapp` | WhatsApp integration (B11) |
| `ai_call` | AI Call module (B12) |
| `contract` | Contract module (B13) |
| `assignment` | Auto-logged on lead assignment |
| `followup` | Follow-up reminder logging |
| `stage_change` | Auto-logged on stage move |
| `status_change` | Auto-logged on status change |
| `file_upload` | Auto-logged on file attach |

### Where to update

The activity type list is typically defined in a config or enum array inside the Admin views and Activity module. Each new type needs:
1. A config/enum entry so the UI can render a human-readable label and icon.
2. Auto-logging hooks where relevant (e.g. `stage_change` on pipeline stage update).

---

## A6. Slack — Missing Inbound Feature: /newlead Slash Command

**What exists:** Lead capture via free-text `lead:` messages.

**What's missing:** The `/newlead` slash command that opens an interactive Slack modal form.

### What needs to be built

**1. Slack App setup:**
- Register a new slash command `/newlead` in the Slack App dashboard pointing to a new endpoint, e.g. `POST /slack/command/newlead`.

**2. New route & controller method:**
```
POST /slack/command/newlead   → opens a modal view
POST /slack/interaction       → handles modal submission
```

Both endpoints must be CSRF-exempt.

**3. Modal fields (per spec Section 4.2):**

| Field | Type | Required |
|---|---|---|
| Full Name | Plain text input | Yes |
| Phone Number | Plain text input | Yes |
| Email | Plain text input | No |
| Source Note | Plain text input | No |
| Lead Value | Plain text input | No |
| Priority | Static select (Hot / Warm / Cold) | No |
| Assign To | Users select | No |
| Notes | Plain text input (multiline) | No |

**4. New `SlackService` methods needed:**
- `openModal(string $triggerId, array $view): array` — calls `views.open`
- `handleModalSubmission(array $payload): void` — parses `view_submission` payload, creates lead

**5. Signature verification** must also cover the interaction endpoint (`POST /slack/interaction`).

---

## A7. Slack — Multi-Channel & Settings UI

**What exists:** A single `SLACK_NOTIFICATION_CHANNEL` env variable. No admin UI for Slack settings.

**What's needed:**

### Database
New table `slack_channels` (or extend workspace settings):

| Column | Type | Notes |
|---|---|---|
| `id` | pk | |
| `channel_name` | string | e.g. `#leads` |
| `channel_id` | string | Slack channel ID (more reliable) |
| `notify_on_new_lead` | boolean | Toggle |
| `notify_on_stage_change` | boolean | Toggle |
| `notify_on_assignment` | boolean | Toggle |
| `webhook_url` | string | Incoming webhook URL (alternative to bot token) |
| `created_at`, `updated_at` | timestamps | |

### Admin UI
- Settings → Integrations → Slack page
- Shows workspace OAuth status (bot token connected / not connected)
- Add / remove / edit channels
- Per-channel toggles for each notification type

---

## A8. Slack — Notify on Lead Assignment (DM)

**What exists:** Slack notifications post to a channel only.

**What's needed:** When a lead is assigned to a user, send a **direct message** to that user's Slack account (if their Slack user ID is known).

### Changes required
1. Add a `slack_user_id` field to the `users` table (or store in user meta/settings).
2. A way to map CRM user → Slack user (could be done via Slack OAuth flow per user, or by manually mapping email to Slack member ID).
3. New `SlackService::sendDm(string $slackUserId, string $text): array` method using `conversations.open` + `chat.postMessage`.
4. New listener for the lead assignment event that calls `sendDm` when `notify_on_assignment` is enabled.

---

---

# SECTION B — New Integrations

---

## B9. Meta Ads Lead Integration

**What exists:** Nothing. No Meta/Facebook module exists anywhere in the codebase.

### Overview
Meta (Facebook & Instagram) sends leads from Lead Ads forms to a webhook in real time. This integration receives those leads and creates CRM records automatically.

### What needs to be built

**1. New package:** `packages/Webkul/MetaAds/`

**2. Webhook endpoint:**
```
GET  /meta/webhook   → Handles Meta's webhook verification (hub.challenge)
POST /meta/webhook   → Receives new lead payloads
```

**3. Lead verification flow:**
- Meta sends a `hub.verify_token` on first setup — respond with `hub.challenge`
- All POST requests include a signature header (`X-Hub-Signature-256`) — verify against `META_APP_SECRET`

**4. New database columns on `leads` table** (requires migration):

| Column | Type | Editable | Notes |
|---|---|---|---|
| `meta_ad_id` | string, nullable | No | FB `ad_id` |
| `meta_adset_id` | string, nullable | No | FB `adset_id` |
| `meta_campaign_id` | string, nullable | No | FB `campaign_id` |
| `meta_form_id` | string, nullable | No | FB `form_id` |
| `meta_page_id` | string, nullable | No | FB `page_id` |
| `platform` | string, nullable | No | `fb` or `ig` |
| `source_created_at` | datetime, nullable | No | Meta lead creation time |

(Note: `campaign_name`, `ad_name`, `form_name` are already planned in A1 above.)

**5. Field mapping from Meta payload:**

| Meta field | CRM field |
|---|---|
| `full_name` / first+last | `persons.name` |
| `email` | `persons.emails` |
| `phone_number` | `persons.contact_numbers` |
| `city` | `persons.city` (A2) |
| `country` | `persons.country` (A2) |
| `ad_id` | `leads.meta_ad_id` |
| `adset_id` | `leads.meta_adset_id` |
| `campaign_id` | `leads.meta_campaign_id` |
| `form_id` | `leads.meta_form_id` |
| `page_id` | `leads.meta_page_id` |
| `campaign_name` | `leads.campaign_name` |
| `ad_name` | `leads.ad_name` |
| `platform` | `leads.platform` |
| `created_time` | `leads.source_created_at` |

**6. Lead source:** Auto-set to "Meta Ads" (seed value from A4).

**7. Environment variables needed:**
```dotenv
META_APP_ID=
META_APP_SECRET=
META_VERIFY_TOKEN=          # your chosen verification token
META_PIXEL_ID=              # optional, for tracking
```

**8. Deduplication:** Check by email or phone before inserting (honours workspace Lead Duplicate Check setting — B18).

---

## B10. Google Ads Lead Form Integration

**What exists:** Nothing. No Google Ads module exists.

### Overview
Google Ads Lead Form Extensions send lead data via a webhook when a user submits a Lead Form ad.

### What needs to be built

**1. New package:** `packages/Webkul/GoogleAds/`

**2. Webhook endpoint:**
```
POST /google/leads/webhook
```

Google sends a POST with a JSON body. Authentication uses a shared secret key in the request header or as a query parameter.

**3. New database columns on `leads` table** (migration):

| Column | Type | Notes |
|---|---|---|
| `gclid` | string, nullable | Google Click ID — never editable |
| `ad_group` | string, nullable | Ad Group Name — editable |

(Note: `campaign_name`, `city`, `country`, `pincode` are already covered in A1/A2.)

**4. Field mapping from Google payload:**

| Google Column ID | CRM field |
|---|---|
| `FULL_NAME` | `persons.name` |
| `EMAIL` | `persons.emails` |
| `PHONE_NUMBER` | `persons.contact_numbers` |
| `CITY` | `persons.city` |
| `COUNTRY` | `persons.country` |
| `POSTAL_CODE` | `persons.pincode` |
| `GCLID` | `leads.gclid` |
| Campaign Name | `leads.campaign_name` |
| Ad Group Name | `leads.ad_group` |

**5. Lead source:** Auto-set to "Google Ads".

**6. Environment variables needed:**
```dotenv
GOOGLE_ADS_WEBHOOK_SECRET=   # Shared secret for request verification
```

---

## B11. WhatsApp Integration

**What exists:** WhatsApp is referenced as an activity type and a notification channel in the spec, but no module exists.

### Overview
Two parts: (1) Outbound — send WhatsApp messages from within a lead's timeline. (2) Inbound — receive WhatsApp messages and log them as activities (requires WhatsApp Business API).

### What needs to be built

**1. New package:** `packages/Webkul/WhatsApp/`

**2. WhatsApp Business API provider** (e.g. Twilio, 360dialog, or Meta Cloud API):
- Implement a `WhatsAppService` with `sendMessage(string $to, string $body): array`
- Support template messages for outbound notifications

**3. Inbound webhook:**
```
POST /whatsapp/webhook   → Receives incoming messages, logs as activity type `whatsapp`
```

**4. Activity logging:**
- Inbound and outbound messages logged as `Activity` with `type = 'whatsapp'`
- Fields: `direction` (inbound/outbound), message text, `status` (sent/delivered/read)

**5. Notification channel:**
- Follow-up reminders delivered via WhatsApp (see B15)
- Notification preference option per user (see B16)

**6. Lead timeline UI:**
- "Send WhatsApp" button on lead detail page
- Shows message history in the timeline

**7. Environment variables needed:**
```dotenv
WHATSAPP_PROVIDER=            # twilio | 360dialog | meta
WHATSAPP_FROM_NUMBER=
WHATSAPP_API_KEY=
WHATSAPP_WEBHOOK_VERIFY_TOKEN=
```

---

## B12. AI Call Module

**What exists:** Nothing. Referenced in the spec as an activity type.

### Overview
Integrates with an AI calling platform (e.g. Bland.ai, Retell, Vapi) to make automated outbound calls and log results.

### What needs to be built

**1. New activity type:** `ai_call`

**2. New database table `ai_calls`:**

| Column | Type | Notes |
|---|---|---|
| `id` | pk | |
| `lead_id` | fk | |
| `activity_id` | fk | Links to the `activities` table entry |
| `provider` | string | Platform used |
| `call_id` | string | External reference from provider |
| `duration` | integer | Seconds |
| `transcript_url` | string, nullable | URL to full transcript |
| `outcome` | string, nullable | e.g. Answered, Voicemail, No Answer |
| `sentiment` | string, nullable | Positive / Neutral / Negative |
| `raw_payload` | json, nullable | Full provider response |
| `created_at`, `updated_at` | timestamps | |

**3. New service:** `AiCallService` — initiates call via provider API, stores result.

**4. Inbound webhook:** Receives call completion callback from the provider, logs `ai_call` activity, updates `outcome` and `transcript_url`.

**5. Lead timeline UI:**
- "Initiate AI Call" button
- Shows call result (duration, outcome, sentiment) in timeline

**6. Environment variables needed:**
```dotenv
AI_CALL_PROVIDER=          # bland | retell | vapi
AI_CALL_API_KEY=
AI_CALL_WEBHOOK_SECRET=
```

---

## B13. Contract Module

**What exists:** `Quote` module exists (`packages/Webkul/Quote/`). No Contract module.

### Overview
Contracts are generated after a quote is accepted. They can be e-signed and their status is tracked.

### What needs to be built

**1. New package:** `packages/Webkul/Contract/`

**2. New database table `contracts`:**

| Column | Type | Notes |
|---|---|---|
| `id` | pk | |
| `lead_id` | fk | |
| `quote_id` | fk, nullable | If generated from a quote |
| `title` | string | Contract name/title |
| `status` | enum | Draft · Sent · Signed · Rejected · Expired |
| `file_path` | string, nullable | PDF stored in Laravel storage |
| `sent_at` | datetime, nullable | |
| `signed_at` | datetime, nullable | |
| `expires_at` | datetime, nullable | |
| `created_by` | fk → users | |
| `created_at`, `updated_at` | timestamps | |

**3. `lead_contracts` pivot table:** Similar to `lead_quotes`.

**4. Activity type `contract`:** Auto-logged when a contract is sent or signed.

**5. Workflow trigger:** `contract.sent.after`, `contract.signed.after` — so workflows can react to contract events.

**6. Timeline UI:** Show contract status, send button, and signed timestamp on lead detail.

---

## B14. Team / Sub-Team Management

**What exists:** `team_id` FK is referenced in the Lead model and spec, but no `teams` table or Team module exists anywhere in the codebase.

### What needs to be built

**1. New database table `teams`:**

| Column | Type | Notes |
|---|---|---|
| `id` | pk | |
| `name` | string | Max 80 chars |
| `description` | text, nullable | Max 300 chars |
| `team_lead_id` | fk → users, nullable | Manager assigned as team lead |
| `created_at`, `updated_at` | timestamps | |

**2. New pivot table `team_users`:**

| Column | Type |
|---|---|
| `team_id` | fk → teams |
| `user_id` | fk → users |

**3. Add `team_id` columns:**
- `leads` table: `team_id` (nullable FK → teams)
- `users` table: default `team_id` (nullable FK → teams)

**4. Models:** `Team`, `TeamProxy` in a new package or inside `Webkul\User`.

**5. Admin UI:** Settings → Teams page (CRUD). Show team members, team lead picker.

**6. Auto-assign:** When auto-assign is enabled (B18), leads are distributed round-robin within the assigned team.

---

## B15. Follow-up Reminder Scheduling

**What exists:** `followup_at` field is planned (A1) but no scheduling/dispatch system exists.

### What needs to be built

**1. New database table `follow_up_reminders`:**

| Column | Type | Notes |
|---|---|---|
| `id` | pk | |
| `lead_id` | fk | |
| `user_id` | fk | Who owns the reminder |
| `remind_at` | datetime | When to fire |
| `type` | enum | call · email · whatsapp · meeting · task |
| `note` | text, nullable | What to do |
| `notify_via` | json | Array: `["in_app", "email", "whatsapp"]` |
| `repeat` | enum | none · daily · weekly · monthly |
| `is_sent` | boolean | Has this reminder fired? |
| `created_at`, `updated_at` | timestamps | |

**2. Scheduled job:** A Laravel `artisan schedule:run` command (or queued job) runs every minute, finds reminders where `remind_at <= now()` and `is_sent = false`, dispatches the notification via each configured channel.

**3. Notification channels:**
- **In-app:** Store in a `notifications` table and push via broadcasting
- **Email:** Send a reminder email via Laravel Mail
- **WhatsApp:** Via WhatsApp integration (B11)

**4. Respects business hours:** If workspace has business hours configured (B18), reschedule reminders that fall outside to the next available window.

**5. Repeat logic:** After sending, if `repeat != 'none'`, create the next reminder automatically.

**6. UI:** "Set Follow-up" button on lead detail page opens a reminder form (date, time, type, note, notify via, repeat).

---

## B16. Per-User Notification Preferences

**What exists:** No notification preferences stored per user. All notifications use global env-var toggles.

### What needs to be built

**1. New database table `user_notification_preferences`:**

| Column | Type | Notes |
|---|---|---|
| `id` | pk | |
| `user_id` | fk → users | |
| `event` | string | e.g. `lead_assigned`, `followup_reminder`, `new_lead` |
| `channel` | enum | `in_app` · `email` · `whatsapp` · `slack_dm` |
| `enabled` | boolean | |
| `created_at`, `updated_at` | timestamps | |

**Notification events to support:**

| Event | Default Channels |
|---|---|
| New Lead Arrived | in_app, email |
| Lead Assigned to Me | in_app |
| Follow-up Reminder | in_app, email |
| Lead Status Changed | in_app |
| Note Mentioning Me | in_app, email |
| Quotation Accepted | in_app, email, slack |
| Contract Signed | in_app, email |
| AI Call Completed | in_app, email |
| Lead Score Changed | — (off by default) |

**2. UI:** User Profile → Notification Settings. A grid of events × channels with toggles.

**3. Consumer:** All notification dispatch logic must check these preferences before sending.

---

## B17. Lead Score Engine

**What exists:** `lead_score` column is planned (A1) but no calculation logic exists.

### What needs to be built

**1. New service:** `LeadScoreService` inside `packages/Webkul/Lead/`

**2. Scoring factors (configurable weights):**

| Factor | Example Score |
|---|---|
| Source quality | Meta/Google = +20, Manual = +10, Website = +15 |
| Activity recency | Activity in last 3 days = +20, last 7 = +10 |
| Email replied | +15 |
| Lead value range | > ₹1L = +10 |
| Profile completeness | Email + Phone + Company all set = +10 |
| Stage advancement | Each stage forward = +5 |
| Overdue follow-up | −10 |
| No activity > 14 days | −15 |

**3. Trigger:** Re-calculate score after:
- `lead.create.after`
- `lead.update.after`
- `activity.create.after` (when activity linked to lead)

**4. Workflow condition:** Allow workflows to be triggered when `lead_score` crosses a threshold.

**5. UI:** Show score as a 0–100 badge on the lead card and detail page, with a tooltip showing the contributing factors.

---

## B18. Workspace Settings Additions

**What exists:** Krayin has a basic settings panel. The following settings from the spec are entirely absent.

### What needs to be built

Each setting below should be stored in the existing workspace settings table (or a new `workspace_settings` key-value store if one doesn't exist).

| Setting | Type | Impact |
|---|---|---|
| **Default Currency** | Dropdown (INR/USD/EUR/GBP/AED/SGD/AUD) | All lead values displayed in this currency |
| **Default Timezone** | Dropdown | Applied to all date/time displays for new users |
| **Date Format** | Dropdown (DD/MM/YYYY, MM/DD/YYYY, YYYY-MM-DD) | Affects all date rendering |
| **Business Hours** | Time range (from–to) | Follow-up reminders respect these hours |
| **Working Days** | Multi-select (Mon–Sun) | Follow-up scheduling, reporting |
| **Lead Duplicate Check** | Dropdown (Email / Phone / Both / Off) | Checked on Meta/Google/Manual lead creation |
| **Auto-Assign Leads** | Toggle | Round-robin assignment to active sales reps |
| **Auto-Assign Pool** | Team or user multi-select | Which reps participate in round-robin |
| **Lead Expiry (days)** | Number (0 = off) | Leads inactive for N days auto-marked Lost |

### Auto-Assign implementation
- Maintain a `last_auto_assigned_at` column on users
- On new lead creation (if auto-assign is on), pick the active rep with the oldest `last_auto_assigned_at`
- Update their `last_auto_assigned_at` timestamp after assignment

### Lead Expiry implementation
- Scheduled daily job: find leads where `updated_at < now() - expiry_days` and `status != lost/won`
- Mark them Lost with `lost_reason = 'Auto-expired after N days of inactivity'`
- Fire `lead.update.after` event so workflows can react

### Email OAuth (Gmail / Outlook)
The current email module accepts SMTP credentials. OAuth connect flows need to be built:
- **Gmail:** OAuth2 via Google Identity Platform (`google/apiclient`)
- **Outlook:** OAuth2 via Microsoft Identity Platform (MSAL)
- Store access + refresh tokens encrypted per user
- Auto-refresh tokens before expiry
