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
├── controller.php          # Package installer
├── blocks/
│   └── lasso_forms/        # Block type files
│       ├── controller.php
│       ├── view.php
│       ├── add.php
│       ├── edit.php
│       ├── form_setup_html.php
│       └── db.xml
└── README.md
```

## API Details

Submissions are sent server-side to:

```
POST https://api.lassocrm.com/v1/registrants
Authorization: Bearer {apiKey}
Content-Type: application/json
```

The payload follows the Lasso v1 registrant schema with `person`, `emails`, `phones`, `addresses`, `notes`, `questions`, and `sourceType` fields in camelCase JSON format.

## Requirements

- Concrete CMS 9.0 or later
- PHP with cURL enabled

## License

See [license](license).
