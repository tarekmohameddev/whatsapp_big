# WhatsApp Evolution API – Template Sending

This document describes the Evolution API Template Sending feature that extends the initial Evolution API integration and Evolution Template creation. It covers backend changes, frontend updates, data flow, payload structure, endpoints, and usage notes for both Single Send and Campaign flows.

## Overview
- Adds support for sending WhatsApp messages via Evolution API using reusable templates.
- Supports these template types and routes them to dedicated Evolution endpoints:
  - simple_txt → /message/sendText/{instance}
  - image → /message/sendMedia/{instance}
  - poll → /message/sendPoll/{instance}
  - list_buttons → /message/sendList/{instance}
- Frontend (User) shows an Evolution Template dropdown when Sending Method = Evolution API.
- Backend merges the selected template payload with the recipient number and dispatches to the correct endpoint.
- Backward compatible: if Evolution is selected but no template is chosen, plain text is sent via the sendText endpoint using the message box content.

## Prerequisites
- Evolution API gateway entries exist with meta_data:
  - instance
  - server
  - token
- Evolution templates exist in the `evolution_whatsapp_templates` table (created via Template UI for Evolution API).

## Data Model
- Table: `evolution_whatsapp_templates`
  - id (bigint, pk)
  - uid (string, unique)
  - user_id (bigint, nullable)
  - name (string)
  - type (enum: simple_txt, image, poll, list_buttons)
  - payload (json) – message body without the recipient number
  - status (enum: active, inactive)
  - timestamps
- Model: `App\Models\EvolutionWhatsappTemplate`
  - Casts: `payload` array, `status` enum, `type` enum (`App\Enums\System\EvolutionWhatsappTemplateTypeEnum`)

## Backend Changes

### Files Updated
- `src/app/Http/Utility/SendWhatsapp.php`
  - Function: `sendEvolutionMessages(...)`
  - New behavior:
    - Reads `evolution_template_id` from `Message.meta_data`.
    - Loads the `EvolutionWhatsappTemplate` by id.
    - Merges `{ "number": "<recipient_number>" }` into the stored `payload`.
    - Picks endpoint based on `type` (see Endpoints below).
    - If no template id present, falls back to sendText with a simple text payload using message body.
    - Uses gateway credentials `server`, `instance`, `token` from `Gateway.meta_data`.

- `src/app/Services/System/Communication/DispatchService.php`
  - Function: `createMessage(...)`
    - Stores `evolution_template_id` in `Message.meta_data` when `method === 'evolution_api'` and a template is selected.
  - Function: `createDispatchLog(...)` and `createCampaignLog(...)`
    - Adds `evolutionTemplates` to the view data (active templates for the current user, names only, for dropdowns).

- `src/app/Jobs/ProcessDispatchLogBatch.php`
  - Ensures WhatsApp message `body` is a string when calling `SendWhatsapp::send(...)` (avoids null type errors); this is method-agnostic and doesn’t alter behavior.

### Endpoints (Evolution API)
- simple_txt → `{server}/message/sendText/{instance}`
- image → `{server}/message/sendMedia/{instance}`
- poll → `{server}/message/sendPoll/{instance}`
- list_buttons → `{server}/message/sendList/{instance}`

### Payload Rules
- Base payload comes from `evolution_whatsapp_templates.payload`.
- System injects/overrides `number`:
```
{
  "number": "+1234567890"
}
```
- Other fields remain as authored in the template payload (e.g., `text`, `mediatype`, `media`, `caption`, `name`, `selectableCount`, `values`, `type`, `sections`, etc.).

### Backward Compatibility
- Other WhatsApp sending methods (Node/Baileys and Meta Cloud API) are unaffected.
- Evolution API without a selected template continues to send plain text using the message box content via sendText.

## Frontend (User) Changes

### Views
- Single Send: `src/resources/views/user/communication/whatsapp/create.blade.php`
  - Shows Evolution gateway select when method = Evolution API (existing).
  - New: Evolution Template dropdown (`evolution_template_id`) appears only when Evolution API is selected.
  - Template list shows names only.

- Campaign Send: `src/resources/views/user/communication/whatsapp/campaign/create.blade.php`
  - Same behavior as Single Send.

### Toggling Logic
- When `#whatsapp_sending_mode` is set to `evolution_api`:
  - Show the Evolution gateway select block.
  - Show the Evolution template dropdown block.
  - Keep message body visible (optional fallback text). If you want templates to be mandatory, hide or disable the message box and add validation – see Future Enhancements.

## Usage

### Single Send
1) Go to User → Communication → WhatsApp → Create
2) Choose `Sending Method` = `Evolution API`
3) Select an `Evolution API Gateway`
4) Select an `Evolution Template` (optional – falls back to simple text if omitted)
5) Provide contacts and send

### Campaign
1) Go to User → Communication → WhatsApp → Campaign → Create
2) Choose `Sending Method` = `Evolution API`
3) Select an `Evolution API Gateway`
4) Select an `Evolution Template` (optional)
5) Configure schedule/logic as needed and create campaign

## Error Handling
- Evolution credentials missing (`server`, `instance`, `token`) → throws `Missing Evolution API credentials`.
- Selected Evolution template not found → throws `Selected Evolution template not found`.
- Network/HTTP errors → exception with Evolution response `message` if present; otherwise, raw body.
- Job dispatcher always passes a string message body to `SendWhatsapp::send(...)` to avoid type errors (independent of Evolution feature).

## Testing Checklist
- Single send with Evolution + simple_txt template.
- Single send with Evolution + image/poll/list_buttons templates.
- Single send with Evolution without template (fallback to text).
- Campaign with Evolution templates across contact lists.
- Node device and Cloud API paths still work as before.
- Linter passes on edited files; no UI regressions for other methods.

## Known Limitations / Notes
- Template payload must conform to Evolution API schemas for the chosen type.
- Server base URL comes from the selected Evolution gateway `meta_data.server`. If centralizing to a single host is desired, override that field at gateway creation or enforce a constant in code.
- Media hosting must be accessible by the Evolution API consumer for media-based templates.

## Future Enhancements
- Make Evolution template mandatory (hide/disable message box and add server-side validation when `method = evolution_api`).
- Add test button for Evolution credentials validation (simple ping endpoint).
- Add per-template preview in UI (names currently shown by design).

## Change Log (Key Commits / Edits)
- `SendWhatsapp::sendEvolutionMessages` – endpoint routing + payload merge with `number` + template support.
- `DispatchService::createMessage` – persists `evolution_template_id` in `Message.meta_data`.
- `DispatchService::createDispatchLog` and `createCampaignLog` – provides `evolutionTemplates` to views.
- User views (Single/Campaign) – Evolution template dropdown; JS toggling for Evolution method.
- `ProcessDispatchLogBatch` – safely passes string body to WhatsApp sender.

---
This feature completes template-based sending for the Evolution API while preserving existing sending methods and flows.
