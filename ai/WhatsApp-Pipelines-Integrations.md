# WhatsApp Pipelines & Integrations

## Overview
Pipelines & Integrations let users connect external systems (e.g., shipping platforms) via a secure webhook and automatically dispatch WhatsApp notifications through existing sending methods without altering the underlying sending/queueing logic.

Key capabilities:
- Per-user integrations exposing a unique webhook URL `POST /api/integrations/{uid}`
- Security via `X-Webhook-Secret` header (plain text password defined per integration)
- Dynamic rules engine selecting method, gateway(s), and template(s) from the incoming JSON body (by dot paths)
- Support for WhatsApp Cloud API templates and Evolution API templates
- Variables mapping from webhook payload into templates and/or message body
- Full request payload is preserved on each dispatch log for traceability

## Data Model

### Tables
- `pipeline_integrations`
  - `id` bigint (pk)
  - `uid` string(100), unique
  - `user_id` int (owner)
  - `name` string
  - `webhook_secret` string
  - `phone_path` string (dot-notation path to recipient phone inside payload)
  - `allowed_methods` json (e.g., `{ "cloud_api": true, "evolution_api": true }`)
  - `allowed_gateways` json (either flat array of IDs or method-keyed arrays)
  - `defaults` json (see Defaults below)
  - `timestamps`

- `pipeline_integration_rules`
  - `id` bigint (pk)
  - `integration_id` bigint (fk → `pipeline_integrations.id` cascade)
  - `name` string
  - `match_path` string (dot-notation path to compare in payload)
  - `operator` enum: `equals|in|not_equals|exists`
  - `value` string (value compared to `match_path`)
  - `action` json (see Actions below)
  - `priority` int (lower runs first; later matches override)
  - `status` enum: `active|inactive`
  - `timestamps`

### Models
- `App\Models\PipelineIntegration`
- `App\Models\PipelineIntegrationRule`

Both cast relevant columns to arrays.

## User Interface
- Sidebar: `Pipelines & Integrations`
- CRUD: `user/pipelines/integrations`
  - Create/Edit:
    - Name, Webhook Password (`X-Webhook-Secret`), Phone Path
    - Allowed Methods (Cloud/Evolution)
    - Allowed Gateways (multi-select per method)
    - Defaults (method + gateway/template dropdowns per method)
    - Rules (repeatable): match path/operator/value + action method + gateway(s)/template (dropdowns) + priority + status

## Webhook
- Method/Path: `POST /api/integrations/{uid}`
- Headers: `X-Webhook-Secret: <password>` (required)
- Body: JSON object sent by external system (free-form)

### Resolution Flow
1. Load `PipelineIntegration` by `{uid}`; verify `X-Webhook-Secret`.
2. Read recipient number using `phone_path` (dot path in body). If not found → ignored.
3. Start with Defaults: `defaults.method`, `defaults.gateway_id`, `defaults.template_id`, `defaults.variables` (optional array of `{ name, path }`).
4. Evaluate active Rules in ascending `priority`:
   - If `match_path` with `operator` vs `value` matches, merge `action` into resolved result.
   - Later matches override previous fields (method/gateway/template/variables).
5. Enforce optional `allowed_methods` and `allowed_gateways` constraints. If not allowed → reject or fallback to defaults gateway.
6. Produce final instruction: `(Recipient Number, Method, Gateway, Template, Variables)`.

### Variables Mapping
- Each mapping is `{ name: string, path: string }`.
- Controller resolves variables by reading `path` from payload.
- For WhatsApp Cloud API templates, resolved variables are also mapped to `body_placeholder_{n}` in request order to reuse the existing template input mechanism.
- If no variable mappings are configured, top-level scalar keys are auto-mapped as a convenience.
- Free-text message bodies have `{{var}}` placeholders replaced by resolved variables; any leftovers are removed.

## Dispatch
- The webhook constructs a synthetic Request and invokes `DispatchService::storeDispatchLogs(WHATSAPP, ...)` to reuse existing sending logic.
- Fields provided:
  - `contacts` (recipient phone)
  - `method` (`cloud_api` or `evolution_api`)
  - `gateway_id`
  - `whatsapp_template_id` (Cloud) or `evolution_template_id` (Evolution)
  - `message[message_body]` (optional plain text)
  - `variables` (resolved variables)
  - `dispatch_meta` (entire webhook payload for auditing)
- The service now persists `webhook_payload` in each dispatch log `meta_data`.

## Security
- `X-Webhook-Secret` must match the configured `webhook_secret` in the user’s integration.
- The endpoint is public but safely scoped to integration `uid` and secret header.

## Supported Methods
- Cloud API (Meta official templates): picks `whatsapp_template_id`; placeholders are fed via `body_placeholder_{n}` and existing UI logic.
- Evolution API: picks `evolution_template_id`. If no template selected, falls back to plain text.

## Failure Behavior
- Missing secret or invalid integration → 401/404.
- Missing recipient number (by `phone_path`) → `{ status: "ignored", reason: "phone_not_found" }`.
- No rule matched and no defaults → `{ status: "ignored", reason: "no_rule_and_no_defaults" }`.

## Examples

### cURL
```bash
curl -X POST "https://your.domain.com/api/integrations/INTEGRATION_UID" \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Secret: YOUR_SECRET" \
  -d '{
    "order": {
      "id": 12345,
      "status": "shipped",
      "customer": { "phone": "+1234567890", "name": "Jane" },
      "category_id": 12,
      "department_id": 3
    }
  }'
```

### Sample Configuration
- Phone Path: `order.customer.phone`
- Defaults:
  - Method: `evolution_api`
  - Evolution Gateway: `<id>`
  - Evolution Template: `<template_id>`
  - Variables: `[ { "name": "order_id", "path": "order.id" }, { "name": "status", "path": "order.status" } ]`
- Rules:
  - Rule A: `match_path=order.category_id`, `operator=equals`, `value=12`
    - Action: Method=`cloud_api`, Cloud Gateway=`<id>`, Cloud Template=`<template_id>`
  - Rule B: `match_path=order.department_id`, `operator=in`, `value=2,3,4`
    - Action: Method=`evolution_api`, Evolution Gateways=`[<id1>,<id2>]`, Evolution Template=`<template_id>`

Result: The final method/template/gateway is derived from the highest-priority matching rule(s), else defaults.

## Notes & Compatibility
- Existing sending logic, queues, and gateway settings are preserved.
- The endpoint temporarily binds the synthetic Request so internal `request()` usage remains compatible.
- Dispatch logs store `meta_data.webhook_payload` and, if provided, message `meta_data.variables`.

## Files & Touchpoints
- Models: `App/Models/PipelineIntegration.php`, `App/Models/PipelineIntegrationRule.php`
- Migrations: `2025_09_01_000000_create_pipeline_integrations_table.php`, `2025_09_01_000010_create_pipeline_integration_rules_table.php`
- User UI: `resources/views/user/pipelines/integrations/*`
- User Controller: `App/Http/Controllers/User/Pipeline/IntegrationController.php`
- Webhook Controller: `App/Http/Controllers/Api/IncomingApi/IntegrationWebhookController.php`
- Dispatch Service: `App/Services/System/Communication/DispatchService.php`
- Route: `routes/api.php` → `Route::post('integrations/{uid}', ...)`

## Migration & Rollout
1. Run database migrations.
2. Navigate to `Pipelines & Integrations` and create an integration.
3. Provide the generated URL and `X-Webhook-Secret` to the external system.
4. Configure Allowed Methods/Gateways, Defaults, and Rules.
5. Test by posting a sample payload; verify dispatch logs and meta data.
