# Lasso CRM for Concrete CMS

A Concrete CMS 9 package that adds a **Lasso CRM Form** block for submitting registrant leads to [Lasso CRM](https://www.lassocrm.com/) via the REST API.

## Installation

1. Copy this directory to `packages/lasso_crm/` in your Concrete CMS site.
2. In the Dashboard, go to **Extend → Add Functionality**.
3. Find **Lasso CRM** and click **Install**.

The package installs the **Lasso CRM Form** block type automatically.

## Usage

1. Edit a page in your site.
2. Add the **Lasso CRM Form** block.
3. Configure the block with your Lasso API credentials and optional question fields.

## Configuration

| Setting | Description |
| --- | --- |
| **Lasso API Key** | Project-scoped Bearer token from Lasso Data Systems. Required. |
| **Signup Thank You Link** | Optional redirect URL after a successful submission. |
| **Thank You Email Template ID** | Optional Lasso auto-reply email template ID. |
| **Question ID** | Optional Lasso question ID for "How did you learn about us?" answers submitted by ID. |
| **Question Label** | Display label and fallback question name when no question ID is set. |
| **Answer Options** | One answer per line in the format `[answerId] Answer label`. |

## Package Structure

```
packages/lasso_crm/
├── controller.php              # Package installer and service registration
├── src/
│   ├── Data/UsStates.php       # US state/province list
│   └── Lasso/
│       ├── RegistrantClient.php
│       ├── RegistrantPayloadBuilder.php
│       ├── QuestionAnswerParser.php
│       └── SubmissionValidator.php
└── blocks/
    └── lasso_forms/
        ├── controller.php
        ├── view.php
        ├── form.php
        ├── composer.php
        ├── add.php
        ├── edit.php
        └── db.xml
```

## Concrete CMS 9 Patterns

This package follows current Concrete CMS 9 conventions:

- PSR-4 autoloading via `$pkgAutoloaderRegistries`
- Service classes registered in the package `on_start()` method
- Guzzle HTTP client for Lasso API requests
- `ErrorList` for block and form validation
- Core `Form` helper in block edit/composer templates
- `composer.php` for Page Type composer support
- Disabled block output caching for POST-handling forms
- Legacy table migration from `btLMSBlockContent` to `btLassoForms` on upgrade

## API Details

Submissions are sent server-side to:

```
POST https://api.lassocrm.com/v1/registrants
Authorization: Bearer {apiKey}
Content-Type: application/json
```

## Requirements

- Concrete CMS 9.0 or later
- PHP with cURL or Guzzle support (included with Concrete CMS)

## License

See [license](license).
