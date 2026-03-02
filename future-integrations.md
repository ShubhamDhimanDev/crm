CRM PLATFORM

Field Reference & UI Specification

All modules · All fields · All dropdowns · Validation rules

Version 1.0 · February 2026

1.  Lead Module

The Lead module is the core of the CRM. A lead is created manually, via
Slack, or auto-captured from Meta / Google. Every field below appears on
the Add / Edit Lead form.

1.1 Basic Information

1.2 Lead Classification

1.3 Assignment & Ownership

1.4 Custom Fields

Admins can add unlimited custom fields per workspace via Settings →
Custom Fields. Each custom field has the following properties:

1.5 Meta Ads Auto-Filled Fields

When a lead arrives via Meta webhook, the following fields are
auto-populated in addition to the basic info above:

1.6 Google Ads Auto-Filled Fields

2.  Pipeline Module

The pipeline is a Kanban-style board. Each column is a Stage. Leads move
across stages by drag-and-drop or by editing the Lead's Pipeline Stage
field. Stages are fully configurable per workspace.

2.1 Pipeline Stage Fields

2.2 Pipeline Card --- Displayed Fields

Each lead card on the Kanban board shows the following information at a
glance:

3.  Activity & Timeline

Every action on a lead is logged to its timeline. Activities appear in
reverse-chronological order on the Lead Detail page.

3.1 Activity Types

3.2 Add Note Form Fields

4.  Slack Integration

4.1 Slack Channel Setup Fields

Accessed via Settings → Integrations → Slack. Each workspace can connect
one or more Slack channels.

4.2 /newlead Slack Command --- Form Fields

When a user types /newlead in the connected channel, a Slack modal pops
up with these fields:

5.  User & Team Management

5.1 User Profile Fields

5.2 Team / Sub-Team Fields

6.  Notifications & Reminders

6.1 Notification Settings Fields

6.2 Follow-up Reminder Fields

7.  Lead List --- Filter & Search Fields

The lead list view has a powerful filter bar. All filters can be
combined and saved as a named View.

8.  Workspace Settings

8.1 General Settings Fields

8.2 Email Integration Settings

--- End of Document ---
