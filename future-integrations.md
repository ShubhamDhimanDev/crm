

**CRM PLATFORM**

Field Reference & UI Specification

All modules · All fields · All dropdowns · Validation rules

Version 1.0  ·  February 2026

# **1\. Lead Module**

The Lead module is the core of the CRM. A lead is created manually, via Slack, or auto-captured from Meta / Google. Every field below appears on the Add / Edit Lead form.

## **1.1 Basic Information**

| Field Label | Field Name | Type | Required | Validation / Notes |
| :---- | :---- | :---- | :---- | :---- |
| First Name | first\_name | Text | Yes | Max 60 chars. Letters, spaces, hyphens only. |
| Last Name | last\_name | Text | Yes | Max 60 chars. |
| Full Name | full\_name | Text (auto) | Auto | Auto-combined from First \+ Last. Read-only display. |
| Email Address | email | Email | Yes | Must be valid email format. Unique per workspace. |
| Phone Number | phone | Phone | Yes | E.164 format. Country code prefix required. e.g. \+919876543210 |
| Alternate Phone | phone\_alt | Phone | No | Optional secondary number. |
| Company Name | company | Text | No | Max 120 chars. |
| Job Title | job\_title | Text | No | Max 100 chars. |
| Website | website | URL | No | Must start with http:// or https:// |
| City | city | Text | No | Max 80 chars. |
| State / Province | state | Text | No | Max 80 chars. |
| Country | country | Dropdown | No | ISO 3166-1 country list. Searchable. |
| Pincode / ZIP | pincode | Text | No | Numeric, max 12 chars. |

## **1.2 Lead Classification**

| Field Label | Field Name | Type | Options / Notes |
| :---- | :---- | :---- | :---- |
| Lead Source | source | Dropdown | Meta Ads · Google Ads · Slack · Manual Entry · Referral · Website · Cold Call · Exhibition · Other |
| Lead Status | status | Dropdown | New · Contacted · Qualified · Proposal Sent · Negotiation · Won · Lost · On Hold |
| Lead Priority | priority | Radio / Tag | Hot · Warm · Cold  (color-coded: Red / Orange / Blue) |
| Lead Score | score | Number (auto) | 0–100. Auto-calculated from activity recency, source quality, engagement. |
| Pipeline Stage | pipeline\_stage\_id | Dropdown | Linked to the workspace Pipeline. Stages are configurable. Default: New Lead. |
| Lead Type | lead\_type | Dropdown | B2B · B2C · Enterprise · SME · Startup · Government |
| Industry | industry | Dropdown | Real Estate · Technology · Healthcare · Finance · Retail · Education · Manufacturing · Other |
| Lead Value (₹) | lead\_value | Currency | Estimated deal value. Numeric, max 15 digits. Shown in pipeline card. |
| Expected Close | expected\_close | Date | Date picker. Must be today or future date. |
| Tags | tags | Multi-select Tag | Free-form or from tag list. Max 10 tags per lead. |
| Campaign Name | campaign\_name | Text (auto) | Auto-filled from Meta/Google webhook. Editable. |
| Ad Name | ad\_name | Text (auto) | Auto-filled from Meta/Google. Editable. |
| Form Name | form\_name | Text (auto) | Name of the lead form the user submitted. Auto-filled. |

## **1.3 Assignment & Ownership**

| Field Label | Field Name | Type | Notes |
| :---- | :---- | :---- | :---- |
| Assigned To | assigned\_to | User Lookup | Searchable dropdown of active team members. |
| Team | team\_id | Dropdown | Which sub-team this lead belongs to. |
| Created By | created\_by | User (auto) | Auto-set to logged-in user. Read-only. |
| Created At | created\_at | DateTime (auto) | Auto-set on creation. Read-only. Shown in IST. |
| Updated At | updated\_at | DateTime (auto) | Auto-updated on every save. Read-only. |
| Last Contacted | last\_contacted\_at | DateTime | Manually set or auto-updated when a note/call is logged. |
| Next Follow-up | followup\_at | DateTime | Date-time picker. Triggers reminder notification. |

## **1.4 Custom Fields**

Admins can add unlimited custom fields per workspace via Settings → Custom Fields. Each custom field has the following properties:

| Property | Type | Notes |
| :---- | :---- | :---- |
| Field Label | Text | Display name shown on the form. Max 60 chars. |
| Field Key | Text (auto) | Snake\_case auto-generated key used in API. Immutable after creation. |
| Field Type | Dropdown | Text · Number · Date · Dropdown · Multi-select · Checkbox · URL · File |
| Required | Toggle | If on, field is mandatory on lead creation. |
| Default Value | Varies | Optional. Pre-fills the field when creating a new lead. |
| Show on Card | Toggle | If on, shows this field on the pipeline Kanban card. |
| Show on List View | Toggle | If on, shows as a column in the lead list table. |
| Order | Number | Display order on the form. Drag-reorderable in settings. |

## **1.5 Meta Ads Auto-Filled Fields**

When a lead arrives via Meta webhook, the following fields are auto-populated in addition to the basic info above:

| Field Label | Field Name | Source in Meta Payload | Editable? |
| :---- | :---- | :---- | :---- |
| Ad ID | meta\_ad\_id | ad\_id | No (read-only) |
| Ad Set ID | meta\_adset\_id | adset\_id | No |
| Campaign ID | meta\_campaign\_id | campaign\_id | No |
| Form ID | meta\_form\_id | form\_id | No |
| Page ID | meta\_page\_id | page\_id | No |
| Campaign Name | campaign\_name | campaign\_name | Yes |
| Ad Name | ad\_name | ad\_name | Yes |
| Platform | platform | platform (fb/ig) | No |
| Created Time | source\_created\_at | created\_time | No |

## **1.6 Google Ads Auto-Filled Fields**

| Field Label | Field Name | Google Column ID | Editable? |
| :---- | :---- | :---- | :---- |
| Full Name | full\_name | FULL\_NAME | Yes |
| Email | email | EMAIL | Yes |
| Phone | phone | PHONE\_NUMBER | Yes |
| City | city | CITY | Yes |
| Country | country | COUNTRY | Yes |
| Postal Code | pincode | POSTAL\_CODE | Yes |
| Google Click ID | gclid | GCLID (meta field) | No |
| Campaign | campaign\_name | Campaign Name | Yes |
| Ad Group | ad\_group | Ad Group Name | Yes |

# **2\. Pipeline Module**

The pipeline is a Kanban-style board. Each column is a Stage. Leads move across stages by drag-and-drop or by editing the Lead's Pipeline Stage field. Stages are fully configurable per workspace.

## **2.1 Pipeline Stage Fields**

| Field Label | Field Name | Type | Required | Notes |
| :---- | :---- | :---- | :---- | :---- |
| Stage Name | name | Text | Yes | Max 60 chars. E.g. New Lead, Follow-up, Proposal, Won. |
| Stage Color | color | Color Picker | No | Hex color. Shown as column header badge. |
| Stage Order | order\_index | Number (auto) | Auto | Drag-to-reorder. Integer position. |
| Stage Type | type | Dropdown | No | Open · Won · Lost. Won/Lost stages trigger deal reporting. |
| Probability % | probability | Number | No | 0–100. Used in weighted pipeline value calculation. |
| Workspace | team\_id | System | Auto | Each workspace has its own independent pipeline. |

## **2.2 Pipeline Card — Displayed Fields**

Each lead card on the Kanban board shows the following information at a glance:

| Field | Source | Notes |
| :---- | :---- | :---- |
| Lead Name | full\_name | Clickable — opens lead detail |
| Company | company | Shown if available |
| Phone | phone | Click-to-call shortcut |
| Lead Value | lead\_value | Shown as ₹ amount |
| Lead Priority | priority | Color dot: Red=Hot, Orange=Warm, Blue=Cold |
| Assigned To | assigned\_to | Avatar \+ name |
| Days in Stage | auto-calculated | How long lead has been in current stage |
| Next Follow-up | followup\_at | Shown as a badge if set and upcoming |
| Tags | tags | First 2 tags shown, \+N for rest |
| Source Icon | source | Meta / Google / Slack / Manual icon |

# **3\. Activity & Timeline**

Every action on a lead is logged to its timeline. Activities appear in reverse-chronological order on the Lead Detail page.

## **3.1 Activity Types**

| Activity Type | type value | Logged By | Fields Captured |
| :---- | :---- | :---- | :---- |
| Note Added | note | Manual | note\_text, created\_by, created\_at |
| Status Changed | status\_change | Auto | from\_status, to\_status, changed\_by |
| Stage Changed | stage\_change | Auto | from\_stage, to\_stage, changed\_by |
| Call Logged | call | Manual | direction (inbound/outbound), duration, outcome, notes |
| Email Sent | email | Auto/Manual | subject, body\_snippet, to\_email, status (sent/opened/bounced) |
| WhatsApp Sent | whatsapp | Auto/Manual | message\_text, direction, status (sent/delivered/read) |
| Lead Assigned | assignment | Auto | assigned\_from, assigned\_to, assigned\_by |
| Follow-up Set | followup | Manual | followup\_at, note, set\_by |
| File Uploaded | file\_upload | Manual | filename, file\_size, uploaded\_by |
| Quotation Sent | quotation | Auto | quotation\_id, total, status |
| Contract Sent | contract | Auto | contract\_id, title, status |
| Lead Created | created | Auto | source, created\_by, created\_at |
| AI Call | ai\_call | Auto | duration, transcript\_url, outcome, sentiment |

## **3.2 Add Note Form Fields**

| Field | Type | Required | Notes |
| :---- | :---- | :---- | :---- |
| Note Text | Rich Text | Yes | Supports bold, italic, lists. Max 2000 chars. |
| Mention User | @mention | No | Tags a team member. They receive an in-app notification. |
| Attach File | File Upload | No | Max 10MB. PDF, DOC, DOCX, PNG, JPG allowed. |
| Pin Note | Toggle | No | Pinned notes appear at the top of the timeline. |
| Visibility | Dropdown | No | Everyone (default) · Only Me · Only Managers |

# **4\. Slack Integration**

## **4.1 Slack Channel Setup Fields**

Accessed via Settings → Integrations → Slack. Each workspace can connect one or more Slack channels.

| Field | Type | Required | Notes |
| :---- | :---- | :---- | :---- |
| Workspace Name | Text (auto) | Auto | Pulled from Slack OAuth. Read-only. |
| Channel Name | Text | Yes | The \#channel where leads will be posted. E.g. \#new-leads |
| Channel ID | Text (auto) | Auto | Slack channel ID. Auto-fetched after OAuth. |
| Bot Token | Token (hidden) | Auto | xoxb- token stored encrypted. Never shown after save. |
| Notify on New Lead | Toggle | No | Post a Slack message every time a new lead arrives. |
| Notify on Stage Change | Toggle | No | Post when a lead moves pipeline stage. |
| Notify on Assignment | Toggle | No | Notify assignee via DM when lead is assigned to them. |
| Webhook URL | URL | Yes | Incoming webhook URL for posting messages to the channel. |

## **4.2 /newlead Slack Command — Form Fields**

When a user types /newlead in the connected channel, a Slack modal pops up with these fields:

| Field | Type | Required | Notes |
| :---- | :---- | :---- | :---- |
| Full Name | Text Input | Yes | Lead's full name. |
| Phone Number | Text Input | Yes | With country code. |
| Email | Text Input | No | Email address. |
| Source Note | Text Input | No | Where did you get this lead? Free text. |
| Lead Value | Text Input | No | Estimated deal value in ₹. |
| Priority | Dropdown | No | Hot / Warm / Cold. |
| Assign To | User Select | No | Select a team member from the Slack workspace. |
| Notes | Text Area | No | Any additional context. Max 500 chars. |

# **5\. User & Team Management**

## **5.1 User Profile Fields**

| Field | Type | Required | Notes |
| :---- | :---- | :---- | :---- |
| Full Name | Text | Yes | Max 100 chars. |
| Email | Email | Yes | Used for login. Unique across the platform. |
| Password | Password | Yes | Min 8 chars. Must include uppercase, number, special char. |
| Phone | Phone | No | For SMS/WhatsApp notifications. |
| Role | Dropdown | Yes | Super Admin · Admin · Manager · Sales Rep · Viewer |
| Team | Dropdown | No | Assign to a sub-team within the workspace. |
| Avatar | Image Upload | No | JPG/PNG. Max 2MB. Shown on lead cards and sidebar. |
| Timezone | Dropdown | No | Affects follow-up reminders and timestamps. |
| Notification Pref. | Multi-select | No | Email · In-App · WhatsApp · Slack DM |
| Status | Toggle | Auto | Active / Inactive. Inactive users cannot log in. |
| Last Login | DateTime (auto) | Auto | Read-only. |

## **5.2 Team / Sub-Team Fields**

| Field | Type | Required | Notes |
| :---- | :---- | :---- | :---- |
| Team Name | Text | Yes | Max 80 chars. |
| Team Lead | User Lookup | No | Assign a manager as team lead. |
| Members | Multi-select User | No | Add/remove team members. |
| Description | Text Area | No | Optional description. Max 300 chars. |

# **6\. Notifications & Reminders**

## **6.1 Notification Settings Fields**

| Notification Event | Channel Options | Default | Configurable By |
| :---- | :---- | :---- | :---- |
| New Lead Arrived | In-App · Email · Slack · WhatsApp | In-App \+ Email | Admin \+ User |
| Lead Assigned to Me | In-App · Email · Slack DM | In-App | User |
| Follow-up Reminder | In-App · Email · WhatsApp | In-App \+ Email | User |
| Lead Status Changed | In-App | In-App | Admin |
| Note Mentioning Me | In-App · Email | In-App | User |
| Quotation Accepted | In-App · Email · Slack | All | Admin \+ User |
| Contract Signed | In-App · Email | All | Admin \+ User |
| AI Call Completed | In-App · Email | In-App | Admin |
| Lead Score Changed | In-App | Off | Admin |

## **6.2 Follow-up Reminder Fields**

| Field | Type | Notes |
| :---- | :---- | :---- |
| Reminder Date | Date Picker | The date of the follow-up. |
| Reminder Time | Time Picker | Time in user's local timezone. |
| Reminder Type | Dropdown | Call · Email · WhatsApp · Meeting · Task |
| Notes | Text Area | What to discuss or do. Max 500 chars. |
| Notify Via | Multi-select | In-App · Email · WhatsApp (uses user notification pref. by default) |
| Repeat | Dropdown | None (default) · Daily · Weekly · Monthly |

# **7\. Lead List — Filter & Search Fields**

The lead list view has a powerful filter bar. All filters can be combined and saved as a named View.

| Filter | Type | Options / Notes |
| :---- | :---- | :---- |
| Search | Text Search | Searches across: name, email, phone, company, notes. |
| Status | Multi-select | New · Contacted · Qualified · Proposal · Negotiation · Won · Lost · On Hold |
| Priority | Multi-select | Hot · Warm · Cold |
| Lead Source | Multi-select | Meta · Google · Slack · Manual · Referral · Website · etc. |
| Pipeline Stage | Multi-select | All stages from the configured pipeline. |
| Assigned To | User Multi-select | Filter by one or more team members. |
| Created Date | Date Range | From – To date picker. |
| Last Contacted | Date Range | Filter by when leads were last contacted. |
| Follow-up Date | Date Range | Show leads with follow-up in a date range. |
| Lead Value | Number Range | Min value – Max value (₹). |
| Lead Score | Number Range | 0–100 slider range. |
| Tags | Tag Multi-select | Filter by one or more tags. |
| Country | Dropdown | ISO country filter. |
| Industry | Dropdown | Industry filter. |
| Team | Dropdown | Filter by sub-team. |
| Has Follow-up | Toggle | Show only leads with a scheduled follow-up. |
| Overdue Follow-up | Toggle | Show leads where follow-up date has passed. |
| No Activity (days) | Number | Leads with zero activity for N+ days. |

# **8\. Workspace Settings**

## **8.1 General Settings Fields**

| Setting | Type | Notes |
| :---- | :---- | :---- |
| Workspace Name | Text | Max 100 chars. Shown in header and emails. |
| Workspace Logo | Image Upload | PNG/JPG. Max 2MB. Used in UI and email templates. |
| Default Currency | Dropdown | INR · USD · EUR · GBP · AED · SGD · AUD. Affects all lead values. |
| Default Timezone | Dropdown | Applied to all date/time displays for new users. |
| Default Language | Dropdown | English (default) · Hindi. UI language. |
| Date Format | Dropdown | DD/MM/YYYY · MM/DD/YYYY · YYYY-MM-DD |
| Business Hours | Time Range | From–To. Follow-up reminders respect business hours. |
| Working Days | Multi-select | Mon–Sun checkboxes. |
| Lead Duplicate Check | Dropdown | Check by: Email · Phone · Both · Off |
| Auto-assign Leads | Toggle | Round-robin assignment to active sales reps. |
| Lead Expiry (days) | Number | Leads inactive for N days auto-marked as Lost. 0 \= Off. |

## **8.2 Email Integration Settings**

| Field | Type | Notes |
| :---- | :---- | :---- |
| Provider | Dropdown | Gmail · Outlook · Custom SMTP |
| Email Address | Email | The from address. |
| OAuth / App Password | Auth | Connect via OAuth button (Gmail/Outlook) or App Password. |
| SMTP Host | Text | Custom SMTP only. E.g. smtp.yourdomain.com |
| SMTP Port | Number | Custom SMTP only. 465 (SSL) or 587 (TLS). |
| Display Name | Text | Sender name shown in recipient's inbox. |
| Email Signature | Rich Text | HTML signature appended to all outgoing emails. |
| BCC All Emails | Email | Optional BCC address for compliance/archiving. |

— End of Document —
