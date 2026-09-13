# Lasso CRM for Concrete CMS

A Concrete CMS 9 package that integrates [Lasso CRM](https://www.lassocrm.com/) for lead capture, inventory display, appointment inquiries, website tracking, and Dashboard administration.

## Installation

1. Copy this directory to `packages/lasso_crm/` in your Concrete CMS site.
2. In the Dashboard, go to **Extend → Add Functionality**.
3. Find **Lasso CRM** and click **Install** (or **Upgrade** if updating from 2.x).

The package installs:

- Dashboard pages under **Dashboard → Lasso CRM**
- Block types: Form, Inventory, Appointments, Website Tracking

## Configure the connection

1. Open **Dashboard → Lasso CRM → Settings**.
2. Enter your project-scoped Lasso API key.
3. Optionally set tracking account ID, default source type, thank-you email template ID, and global tracking.
4. Click **Test Connection** to verify GET `/projects/settings`.

Blocks use the package API key by default. Each block can optionally override the key for multi-project sites.

## Blocks

### Lasso CRM Form

Public registrant lead form. Submits to `POST /registrants` with camelCase JSON, CSRF protection, optional project questions from Lasso settings, and website tracking GUID when available.

### Lasso Inventory

Lists inventory from `GET /inventory` with optional status filter, max items, and column toggles for price / plan / availability.

### Lasso Appointments

- **Inquiry mode** — creates a registrant, then attempts `POST /registrants/{id}/appointments` (falls back to a registrant note if appointment create fails).
- **List mode** — shows upcoming items from `GET /projects/appointments`.
- **Both** — list plus inquiry form.

### Lasso Website Tracking

Injects Lasso Analytics v2 (`pageView` + `patchRegistrationForms`). Prefer enabling **global tracking** in Settings; use this block for page-specific account overrides.

## Package Structure

```
packages/lasso_crm/
├── controller.php
├── controllers/single_page/dashboard/
│   ├── lasso_crm.php
│   └── lasso_crm/settings.php
├── single_pages/dashboard/lasso_crm/
│   ├── view.php
│   └── settings.php
├── src/
│   ├── Data/UsStates.php
│   ├── Lasso/
│   │   ├── ApiClient.php
│   │   ├── ConnectionConfig.php
│   │   ├── QuestionAnswerParser.php
│   │   ├── RegistrantClient.php
│   │   ├── RegistrantPayloadBuilder.php
│   │   └── SubmissionValidator.php
│   └── Tracking/AnalyticsInjector.php
└── blocks/
    ├── lasso_forms/
    ├── lasso_inventory/
    ├── lasso_appointments/
    └── lasso_tracking/
```

## API

Base URL: `https://api.lassocrm.com/v1`

```
Authorization: Bearer {apiKey}
Content-Type: application/json
```

Used endpoints include registrants (create/list), project settings, inventory, project appointments, registrant appointments, and notes.

## Requirements

- Concrete CMS 9.0 or later
- PHP with Guzzle (included with Concrete CMS)

## License

See [license](license).
