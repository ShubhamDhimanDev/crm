# Slack Integration — Krayin CRM

This document covers how to set up the Slack App and how to create leads directly from Slack channel messages.

---

## Table of Contents

1. [Slack App Setup](#1-slack-app-setup)
2. [Environment Variables](#2-environment-variables)
3. [Invite the Bot to Your Channels](#3-invite-the-bot-to-your-channels)
4. [Creating Leads from Slack Messages](#4-creating-leads-from-slack-messages)
5. [Outbound CRM Notifications](#5-outbound-crm-notifications)
6. [Automation Workflow Action](#6-automation-workflow-action)
7. [Troubleshooting](#7-troubleshooting)

---

## 1. Slack App Setup

### Step 1 — Create the App

1. Go to **[https://api.slack.com/apps](https://api.slack.com/apps)**
2. Click **Create New App** → choose **From scratch**
3. Enter a name (e.g. `Krayin CRM`) and select your Slack workspace
4. Click **Create App**

---

### Step 2 — Add Bot Token Scopes

1. In the left sidebar go to **OAuth & Permissions**
2. Scroll down to **Bot Token Scopes**
3. Click **Add an OAuth Scope** and add the following:

| Scope | Why it's needed |
|---|---|
| `chat:write` | Post messages to channels |
| `chat:write.public` | Post to public channels without joining |
| `channels:history` | Read messages in public channels |
| `groups:history` | Read messages in private channels *(optional)* |
| `im:history` | Read direct messages *(optional)* |

---

### Step 3 — Install the App to Your Workspace

1. Still on the **OAuth & Permissions** page, scroll up and click **Install to Workspace**
2. Click **Allow**
3. Copy the **Bot User OAuth Token** — it starts with `xoxb-`

> Keep this token secret. Add it to your `.env` as `SLACK_BOT_TOKEN`.

---

### Step 4 — Enable the Events API

1. In the left sidebar go to **Event Subscriptions**
2. Toggle **Enable Events** to **On**
3. In the **Request URL** field enter your CRM webhook URL:

```
https://your-crm-domain.com/slack/webhook
```

> **Local development?** Use [ngrok](https://ngrok.com) or [Expose](https://expose.dev) to tunnel your local server:
> ```bash
> ngrok http 8000
> # Use the generated https URL, e.g. https://abc123.ngrok.io/slack/webhook
> ```

4. Slack will immediately send a challenge request. Your app will respond automatically and show **Verified ✓**

---

### Step 5 — Subscribe to Bot Events

Still on the **Event Subscriptions** page, scroll down to **Subscribe to bot events** and add:

| Event | Why it's needed |
|---|---|
| `message.channels` | Receive messages from public channels |
| `message.groups` | Receive messages from private channels *(optional)* |

Click **Save Changes**.

---

### Step 6 — Copy the Signing Secret

1. In the left sidebar go to **Basic Information**
2. Scroll to **App Credentials**
3. Copy the **Signing Secret**

> Add it to your `.env` as `SLACK_SIGNING_SECRET`. This is used to verify that webhook requests genuinely come from Slack.

---

## 2. Environment Variables

Add the following to your `.env` file:

```dotenv
# ── Slack Integration ──────────────────────────────────────────────────────────

# Bot User OAuth Token (starts with xoxb-)
SLACK_BOT_TOKEN=xoxb-your-bot-token-here

# Signing Secret — found under Basic Information → App Credentials
SLACK_SIGNING_SECRET=your-signing-secret-here

# Default Slack channel for CRM notifications
# Use the channel name (#leads) or the channel ID (C0123ABCDEF) for reliability
SLACK_NOTIFICATION_CHANNEL=#leads

# Capture leads from channel messages starting with "lead:"
SLACK_LEAD_CAPTURE_ENABLED=true

# Outbound notification toggles
SLACK_NOTIFY_LEAD_CREATED=true
SLACK_NOTIFY_LEAD_UPDATED=false
SLACK_NOTIFY_LEAD_STAGE_CHANGED=true
```

---

## 3. Invite the Bot to Your Channels

The bot must be a **member** of any channel where you want it to receive messages and post replies.

In Slack, open the channel (e.g. `#leads`) and type:

```
/invite @KrayinCRM
```

Repeat for every channel you want the bot to monitor.

---

## 4. Creating Leads from Slack Messages

Post a message in any channel the bot is a member of. The message **must start with `lead:`** (case-insensitive). Fields go on separate lines in `Field: Value` format.

The bot will:
1. Parse the message
2. Create the lead (and contact) in the CRM
3. Reply in-thread with a confirmation

---

### Supported Fields

| Field label(s) accepted | Maps to |
|---|---|
| `Name`, `Contact`, `Person`, `Full Name` | Contact name |
| `Email`, `Mail`, `E-mail` | Contact email |
| `Phone`, `Mobile`, `Tel`, `Telephone`, `Cell`, `Contact Number` | Contact phone |
| `Company`, `Org`, `Organization`, `Business`, `Firm`, `Account` | Organisation |
| `Value`, `Deal Value`, `Amount`, `Budget`, `Price`, `Deal Size` | Lead value (numeric) |
| `Notes`, `Note`, `Description`, `Desc`, `Details`, `Message`, `Comment` | Lead description |
| `Title`, `Lead Title`, `Subject`, `Opportunity` | Lead title |

---

### Example 1 — Basic Lead

```
lead:
Name: Sarah Johnson
Phone: +1 555 234 5678
Email: sarah@example.com
```

**CRM creates:**
- Lead: *"Lead from Sarah Johnson"*
- Contact: *Sarah Johnson* with email and phone
- Source: *Slack*

**Bot replies in thread:**
```
✅ Lead "Lead from Sarah Johnson" created in CRM for contact Sarah Johnson. (ID #87)
```

---

### Example 2 — Full Lead with Company and Deal Value

```
lead:
Title: Enterprise SaaS Deal
Name: Michael Torres
Email: m.torres@acmecorp.com
Phone: +44 20 7946 0123
Company: Acme Corp
Value: 25000
Notes: Interested in the enterprise plan. Has a team of 50. Follow up by end of March.
```

**CRM creates:**
- Lead: *"Enterprise SaaS Deal"* with value *$25,000.00*
- Contact: *Michael Torres* linked to organisation *Acme Corp*
- Source: *Slack*

**Bot replies in thread:**
```
✅ Lead "Enterprise SaaS Deal" created in CRM for contact Michael Torres | Value: $25,000.00. (ID #88)
```

---

### Example 3 — Referral Lead with Alternate Field Labels

```
lead:
Full Name: Priya Sharma
Mobile: +91 98765 43210
Mail: priya@startup.io
Organisation: Startup IO
Budget: 8000
Description: Met at TechConf 2026. Wants CRM + email automation. Decision maker.
```

**CRM creates:**
- Lead: *"Lead from Priya Sharma"* with value *$8,000.00*
- Contact: *Priya Sharma* linked to organisation *Startup IO*

---

### Example 4 — Phone-only Quick Lead

```
lead:
name: David Kim
phone: 07700 900123
notes: Cold call — left voicemail. Call back Thursday.
```

**CRM creates:**
- Lead: *"Lead from David Kim"* with phone number
- No email required — contact is created with just the name and phone

---

### Example 5 — Minimal One-liner Style

The bot also tolerates compact formatting as long as `lead:` is on the first line:

```
lead:
Name: Chen Wei
Email: chen@globaltech.cn
Value: 50000
```

---

## 5. Outbound CRM Notifications

When leads are created or updated in the CRM (from **any** source — web form, admin panel, email, API), the bot posts a notification to your configured channel.

### Lead Created Notification

```
🎯 New Lead: Enterprise SaaS Deal

Contact        Deal Value
Michael Torres $25,000.00

Assigned To    Source
Jane Owen      Slack

Notes:
Interested in the enterprise plan. Has a team of 50.
```

### Lead Stage Changed Notification

```
🔄 Lead "Enterprise SaaS Deal" (Michael Torres) moved to Proposal Sent.
```

Toggle these notifications individually in `.env`:

```dotenv
SLACK_NOTIFY_LEAD_CREATED=true
SLACK_NOTIFY_LEAD_UPDATED=false
SLACK_NOTIFY_LEAD_STAGE_CHANGED=true
```

---

## 6. Automation Workflow Action

The integration adds a **"Send Slack Notification"** action to the CRM's built-in Automation Workflows.

**Setup:**
1. Go to **Settings → Workflows** in the CRM admin
2. Create or edit a workflow (e.g. triggered on *Lead Created* or *Lead Updated*)
3. Add an action → select **Send Slack Notification**
4. Optionally enter a specific channel (e.g. `#sales-alerts`) — leave blank to use the default channel

The action posts a message like:
```
🔔 Workflow Alert — Lead "Enterprise SaaS Deal" (Contact: Michael Torres | Stage: Qualified | Value: $25,000.00)
```

---

## 7. Troubleshooting

### Slack shows "Your URL didn't respond with the value of the challenge parameter"

- Make sure your server is publicly accessible (use ngrok for local dev)
- Check that `slack/webhook` is in the `$except` list in `app/Http/Middleware/VerifyCsrfToken.php`
- Check Laravel logs: `storage/logs/laravel.log`

### Lead is not being created

- Confirm the message starts exactly with `lead:` on the first line
- Make sure `SLACK_LEAD_CAPTURE_ENABLED=true` is set in `.env`
- Make sure the bot is a member of the channel (`/invite @YourBotName`)
- Check Laravel logs for `[Slack]` prefixed error entries

### Bot is not posting notifications

- Verify `SLACK_BOT_TOKEN` is set correctly (starts with `xoxb-`)
- Confirm `chat:write` and `chat:write.public` scopes are added
- Verify the bot is a member of `SLACK_NOTIFICATION_CHANNEL`
- Re-install the app in your workspace if you added new scopes after initial install

### Signature verification failing (401 responses)

- Confirm `SLACK_SIGNING_SECRET` matches the value in **Slack App → Basic Information → App Credentials**
- Ensure there are no extra spaces or newline characters in `.env`
- Check that your server clock is not skewed by more than 5 minutes (NTP sync)
