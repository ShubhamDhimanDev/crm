# Project Context — Krayin CRM (Custom Build)

Version 1.0 · March 2026

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Tech Stack](#2-tech-stack)
3. [Repository & Package Structure](#3-repository--package-structure)
4. [What Krayin CRM Provides Out of the Box](#4-what-krayin-crm-provides-out-of-the-box)
5. [Custom Work Already Completed](#5-custom-work-already-completed)
6. [Environment Variables Reference](#6-environment-variables-reference)
7. [Key File Locations](#7-key-file-locations)

---

## 1. Project Overview

This is a customised installation of **[Krayin CRM](https://krayincrm.com)** by Webkul — an open-source Laravel-based CRM. The base product has been extended with a fully custom Slack integration built as a dedicated Laravel package (`Webkul\Slack`).

The CRM is used to manage leads, contacts, organisations, pipelines, activities, quotes, and automation workflows. The Slack integration allows the team to create leads directly from Slack channel messages and receive outbound CRM notifications in Slack.

---

## 2. Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel (PHP) |
| CRM Base | Krayin CRM by Webkul |
| Package Architecture | Modular — `packages/Webkul/*` |
| Frontend Build | Vite (`vite.config.js`) |
| Database | MySQL / MariaDB (via Laravel migrations) |
| Queue | Configurable — sync (default), Redis recommended for Slack delays |
| HTTP Client | GuzzleHTTP (used in Slack & Webhook services) |
| Testing | PestPHP |
| Code Style | Laravel Pint |

---

## 3. Repository & Package Structure

```
packages/
  Webkul/
    Activity/       — Activity & timeline logging (native Krayin)
    Admin/          — Admin panel controllers, views, notifications
    Attribute/      — Custom fields engine (EAV-style)
    Automation/     — Workflow engine (triggers, conditions, actions)
    Contact/        — Person & Organisation models
    Core/           — Shared helpers, service providers
    DataGrid/       — Reusable data table component
    DataTransfer/   — Import/export functionality
    Email/          — Inbound/outbound email handling
    EmailTemplate/  — Email template management
    Installer/      — Web-based installer
    Lead/           — Lead model, pipeline, stages, sources, types
    Marketing/      — Marketing campaigns
    Product/        — Product catalogue (linked to leads/quotes)
    Quote/          — Quotation module
    Slack/          — ★ CUSTOM — Full Slack integration (see §5)
    Tag/            — Tag management
    User/           — User & role management
    Warehouse/      — Warehouse/inventory (native Krayin)
    WebForm/        — Public-facing web lead capture forms
```

---

## 4. What Krayin CRM Provides Out of the Box

### 4.1 Lead Module (`Webkul\Lead`)

**Database columns (leads table):**
- `title`, `description`, `lead_value`, `status`, `lost_reason`
- `expected_close_date`, `closed_at`
- `user_id` (assigned to), `person_id`, `lead_source_id`, `lead_type_id`
- `lead_pipeline_id`, `lead_pipeline_stage_id`

**Supporting tables:**
- `lead_sources` — configurable source lookup (e.g. Manual Entry, Website)
- `lead_types` — configurable type lookup (B2B, B2C, etc.)
- `lead_pipelines` — named pipelines (with `rotten_days` support)
- `lead_pipeline_stages` — stages per pipeline (name, code, sort order)
- `lead_tags` — pivot for tag assignment
- `lead_products` — pivot for products linked to a lead
- `lead_quotes` — pivot for quotes linked to a lead
- `lead_activities` — pivot for activity assignment to leads

**Models & traits:**
- `Lead` model with `CustomAttribute` (EAV custom fields) and `LogsActivity` traits
- Proxy pattern used throughout for extensibility

### 4.2 Contact Module (`Webkul\Contact`)

**Person:**
- `name`, `job_title`, `user_id`, `organization_id`, `unique_id`
- `emails` — JSON array: `[{ "value": "...", "label": "work" }]`
- `contact_numbers` — JSON array: `[{ "value": "...", "label": "work" }]`

**Organisation:**
- Name, linked persons, activities

### 4.3 Pipeline / Kanban (`Webkul\Lead`)

- Multiple named pipelines per workspace
- Configurable stages per pipeline with `sort_order` and `code`
- `rotten_days` setting per pipeline (flags stale leads)
- Pipeline stage uniqueness enforced (migration `2025_07_01`)

### 4.4 Activity & Timeline (`Webkul\Activity`)

- `Activity` model with `type` (free-form string), `title`, `comment`, `schedule_from`, `schedule_to`, `is_done`
- `File` model for file attachments on activities
- `Participant` model for activity participants
- `LogsActivity` trait auto-logs changes on Lead and Person models

**Supported activity types (native UI):**
`call`, `meeting`, `lunch`, `email`, `note`

### 4.5 Custom Fields (`Webkul\Attribute`)

- EAV-style custom field engine
- Field types: text, textarea, price, boolean, select, multiselect, checkbox, email, address, phone, image, file, date, datetime
- Configured per entity type (`leads`, `persons`, `organisations`)
- Rendered automatically on forms via the `CustomAttribute` trait

### 4.6 Automation Workflows (`Webkul\Automation`)

- Workflow model with `event`, `conditions` (JSON), `actions` (JSON)
- Supported trigger events: `lead.create.after`, `lead.update.after`
- Condition types: attribute match, tag match, etc.
- Built-in actions:
  - `update_lead` — update any lead attribute
  - `update_person` — update any person attribute
  - `send_email_to_person` — from email template
  - `send_email_to_sales_owner` — from email template
  - `add_tag` — tag the lead
  - `add_note_as_activity` — log a note
  - `trigger_webhook` — POST to external URL (with headers, payload, query params)
  - `send_slack_notification` — ★ custom action (see §5.3)

### 4.7 Other Native Modules

| Module | What it provides |
|---|---|
| `Quote` | Quotation generation linked to leads |
| `Product` | Product catalogue with pricing |
| `Email` | Inbound email (IMAP) + outbound email per lead |
| `EmailTemplate` | HTML email templates for workflows |
| `Tag` | Shared tag library across entities |
| `WebForm` | Public lead capture forms with embed script |
| `DataTransfer` | CSV import/export for leads and contacts |
| `Marketing` | Campaign management |
| `User` | Users, roles (Super Admin, Admin, Manager, Sales Rep, Viewer), ACL |

---

## 5. Custom Work Already Completed

### 5.1 Slack Package — `packages/Webkul/Slack/`

```
Slack/src/
  Config/
    slack.php              — All Slack config keys mapped from .env
  Http/
    Controllers/
      SlackWebhookController.php  — Handles all inbound Slack Events API requests
  Listeners/
    LeadEventListener.php  — Fires Slack notifications on lead.create / lead.update
  Providers/
    SlackServiceProvider.php
  Routes/
    web.php                — POST /slack/webhook (CSRF-exempt)
  Services/
    SlackService.php       — Slack Web API wrapper (postMessage, Block Kit, notifications)
    LeadParser.php         — Parses "lead:" messages into structured lead data
```

---

### 5.2 Inbound: Lead Capture from Slack Messages

**How it works:**
1. A Slack channel member posts a message starting with `lead:` (case-insensitive).
2. Slack sends a `message.channels` event to `POST /slack/webhook`.
3. `SlackWebhookController` verifies the request signature using `SLACK_SIGNING_SECRET`.
4. `LeadParser` parses the message body into canonical fields.
5. The controller creates a `Person` (and `Organisation` if a company is provided) and a `Lead` via the respective repositories.
6. `SlackService::postReply()` sends a thread confirmation back to Slack.

**Supported field aliases (LeadParser):**

| Canonical Field | Accepted Labels |
|---|---|
| `name` | Name, Contact, Person, Contact Name, Full Name |
| `phone` | Phone, Mobile, Tel, Telephone, Cell, Contact Number |
| `email` | Email, Mail, E-mail |
| `company` | Company, Org, Organization, Organisation, Business, Firm, Account |
| `value` | Value, Deal Value, Amount, Budget, Price, Deal Size, Revenue |
| `notes` | Notes, Note, Description, Desc, Info, Details, Message, Comment |
| `title` | Title, Lead Title, Subject, Opportunity |
| `lost_reason` | Lost Reason, Reason, Loss Reason, Why Lost |
| `expected_close_date` | Close Date, Expected Close, Expected Close Date, Closing Date, Close By, Deadline |

**Extra parsing features:**
- Slack auto-link stripping (`<mailto:x|x>`, `<tel:+1|+1>`, `<https://x|x>`)
- Human-readable date parsing for `expected_close_date`
- Numeric cleaning for `value` (strips `$`, `,`, etc.)

**Thread reply format:**
```
✅ Lead "Enterprise SaaS Deal" created in CRM for contact Michael Torres | Value: $25,000.00. (ID #88)
```

---

### 5.3 Outbound: CRM → Slack Notifications

Triggered by `LeadEventListener` which listens on `lead.create.after` and `lead.update.after` events.

**Lead Created** (`SLACK_NOTIFY_LEAD_CREATED=true`):
- Posts a Block Kit message to `SLACK_NOTIFICATION_CHANNEL`
- Shows: lead title, contact name, deal value, assigned user, source, notes

**Lead Updated** (`SLACK_NOTIFY_LEAD_UPDATED=false` by default):
- Posts a plain-text update: `✏️ Lead Title (Contact) was updated.`

**Stage Changed** (`SLACK_NOTIFY_LEAD_STAGE_CHANGED=true`):
- Posts: `🔄 Lead Title (Contact) moved to Stage Name.`
- Won → `🏆` message with value and closed date
- Lost → `❌` message with lost reason and closed date
- Throttled with a cache-based 10-second settling window to prevent duplicate notifications on rapid kanban moves

---

### 5.4 Workflow Action: Send Slack Notification

Available in Settings → Workflows as an action type `send_slack_notification`.

Implemented in `Webkul\Automation\Helpers\Entity\Lead::executeActions()`.

Posts a workflow alert message:
```
🔔 Workflow Alert — Lead "Title" (Contact: Name | Stage: Stage | Value: $X)
```

Optionally accepts a custom `channel` in the workflow action config; falls back to `SLACK_NOTIFICATION_CHANNEL`.

---

### 5.5 Signature Verification

`SlackWebhookController::verifySignature()`:
- Reads raw request body and `X-Slack-Request-Timestamp` header
- Rejects requests older than 5 minutes (replay attack protection)
- Computes `HMAC-SHA256(v0:{timestamp}:{body}, SLACK_SIGNING_SECRET)`
- Uses `hash_equals()` for timing-safe comparison

---

## 6. Environment Variables Reference

```dotenv
# ── Slack Integration ──────────────────────────────────────────────────────────

# Bot User OAuth Token (starts with xoxb-)
SLACK_BOT_TOKEN=xoxb-your-bot-token-here

# Signing Secret — Basic Information → App Credentials in Slack dashboard
SLACK_SIGNING_SECRET=your-signing-secret-here

# Default channel for CRM notifications (name or channel ID)
SLACK_NOTIFICATION_CHANNEL=#leads

# Enable/disable inbound lead capture from channel messages
SLACK_LEAD_CAPTURE_ENABLED=true

# Outbound notification toggles
SLACK_NOTIFY_LEAD_CREATED=true
SLACK_NOTIFY_LEAD_UPDATED=false
SLACK_NOTIFY_LEAD_STAGE_CHANGED=true
```

---

## 7. Key File Locations

| What | Path |
|---|---|
| Lead model | `packages/Webkul/Lead/src/Models/Lead.php` |
| Lead migrations | `packages/Webkul/Lead/src/Database/Migrations/` |
| Person model | `packages/Webkul/Contact/src/Models/Person.php` |
| Activity model | `packages/Webkul/Activity/src/Models/Activity.php` |
| Workflow actions (lead) | `packages/Webkul/Automation/src/Helpers/Entity/Lead.php` |
| Webhook service | `packages/Webkul/Automation/src/Services/WebhookService.php` |
| Slack config | `packages/Webkul/Slack/src/Config/slack.php` |
| Slack routes | `packages/Webkul/Slack/src/Routes/web.php` |
| Slack webhook controller | `packages/Webkul/Slack/src/Http/Controllers/SlackWebhookController.php` |
| Slack service | `packages/Webkul/Slack/src/Services/SlackService.php` |
| Lead parser | `packages/Webkul/Slack/src/Services/LeadParser.php` |
| Lead event listener | `packages/Webkul/Slack/src/Listeners/LeadEventListener.php` |
| CSRF middleware | `app/Http/Middleware/VerifyCsrfToken.php` |
| App routes | `routes/web.php` |
