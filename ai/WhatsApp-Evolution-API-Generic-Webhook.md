# WhatsApp Evolution API – Generic Webhook

## Overview
- Purpose and scope of the generic webhook for Evolution API list selections
- High-level flow: receive → resolve user → match row → execute HTTP action

## Prerequisites
- Evolution WhatsApp gateway configured with `meta_data.server`, `meta_data.instance`, `meta_data.token`
- Evolution templates created of type `list_buttons`
- Optional: downstream URLs available to receive HTTP callbacks

## Data Model
- Table: `evolution_whatsapp_templates`
  - Fields: `id`, `uid`, `user_id`, `name`, `type`, `payload`, `row_actions`, `status`, timestamps
- Model: `App\Models\EvolutionWhatsappTemplate`
  - Casts: `payload` array, `row_actions` array
- `row_actions` schema (per `rowId`)
  - `enabled` (bool)
  - `method` (GET|POST|PUT|PATCH|DELETE)
  - `url` (string)
  - `headers` (map<string,string>)
  - `body` (object)

## Route
- Method/Path: `POST /api/whatsapp/evolution/webhook`
- Middleware: public (no `incoming.api`), accepts JSON body
- Controller: `App\Http\Controllers\Api\IncomingApi\EvolutionWebhookController@handle`

## Authentication & User Resolution
- Extract `apikey` from request body `apikey` or headers `apikey`/`ApiKey`
- Optionally use `server_url` to further constrain gateway lookup
- Map to `Gateway` where:
  - `channel = whatsapp`, `type = evolution`, `status = active`
  - `meta_data->token = apikey`
  - (optional) `meta_data->server = server_url`
- `user_id` is taken from the matched gateway

## Incoming Payload (Example)
- Expected fields (from Evolution):
  - `event`, `instance`, `data`, `sender`, `server_url`, `apikey`, etc.
  - `data.message.listResponseMessage.singleSelectReply.selectedRowId`
- Required minimums for processing:
  - `apikey`, `selectedRowId`, `sender`

## Selected Row Extraction
- Read `selectedRowId` from `data.message.listResponseMessage.singleSelectReply.selectedRowId`
- Normalize `sender` from JID to phone (strip `@s.whatsapp.net`)

## Template & Row Matching
- Search active `list_buttons` templates for `user_id`
- Scan `payload.sections[].rows[].rowId` for match with `selectedRowId`
- On match, use the same template’s `row_actions[selectedRowId]`

## HTTP Action Dispatch
- Validate action: `enabled=true` and valid `url`
- Method: `GET|POST|PUT|PATCH|DELETE`
- Headers: optional map
- Body: optional object, merged with `meta` context
  - `meta = { selectedRowId, sender, instance, gateway_id, user_id, raw }`
- Timeout: 10s (default)
- Response: return `processed` with downstream status/body (truncated)

## Error Handling
- Missing required fields → `status=ignored` (200)
- Gateway not found/unassigned → `status=ignored` (200)
- Template/row not found → `status=ignored` (200)
- HTTP errors/exceptions → `status=error` (200) with message

## Security Notes
- Webhook is public; validation relies on `apikey` matching a configured Evolution gateway
- Consider IP allowlisting or signature verification if supported later

## Testing Checklist
- Valid payload with matching `apikey` and `selectedRowId` triggers HTTP action
- Non-matching token → ignored
- Matching token but no row action → ignored
- Create/edit views save `row_actions` correctly for `list_buttons`
- Backward compatibility: other template types unaffected

## Implementation Pointers
- Controller: `src/app/Http/Controllers/Api/IncomingApi/EvolutionWebhookController.php`
- Route: `src/routes/api.php` → `POST /api/whatsapp/evolution/webhook`
- Model: `src/app/Models/EvolutionWhatsappTemplate.php`
- Migration (actions column): `src/database/migrations/2025_08_28_000200_add_row_actions_to_evolution_whatsapp_templates_table.php`
- Views: `src/resources/views/user/template/whatsapp/evolution/{create,edit}.blade.php`
- Controller (templates): `src/app/Http/Controllers/User/Template/WhatsappEvolutionTemplateController.php`

## Future Enhancements
- Add optional HMAC signature verification
- Persist webhook call logs and outcomes
- Support action templating (variables expansion) beyond `meta`
- Rate limiting and retry strategies

## Change Log (Key Edits)
- New controller for webhook handling
- Public API route for webhook
- Model cast/fillable for `row_actions`
- New migration to add `row_actions` column
- UI changes to capture per-row HTTP actions for `list_buttons`


