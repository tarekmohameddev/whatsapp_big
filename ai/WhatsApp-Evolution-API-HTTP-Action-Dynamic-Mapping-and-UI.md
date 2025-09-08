## WhatsApp (Evolution API) – HTTP Action Dynamic Mapping & UI Enhancements

This document describes the new feature that enables dynamic variable mapping in HTTP actions triggered by list button selections, and the associated UI improvements for creating/editing Evolution WhatsApp templates.

### Overview
- Add dynamic placeholders and path-based interpolation to HTTP action bodies and headers.
- Resolve values from the webhook context, including the original dispatch `webhook_payload`.
- Reorganized UI for HTTP actions in Evolution template create/edit views with validation and helper tooling.

### Affected Areas
- Backend:
  - `src/app/Http/Controllers/Api/IncomingApi/EvolutionWebhookController.php`
- Frontend (User):
  - `src/resources/views/user/template/whatsapp/evolution/create.blade.php`
  - `src/resources/views/user/template/whatsapp/evolution/edit.blade.php`

### Backend Changes
1) Dynamic body interpolation
   - Placeholders in JSON strings are resolved against a context object.
   - Supported syntaxes:
     - Placeholder: `{{ path.to.value }}` (with optional default: `{{ path.to.value || default }}`)
     - Path directive (replace entire object): `{ "$path": "path.to.value" }`

2) Interpolation context
   - Always includes:
     - `selectedRowId`, `sender`, `customer` (normalized phone), `instance`, `gateway_id`, `user_id`, `raw` (incoming request body)
   - Also includes (if found):
     - `webhook_payload` – original payload saved at dispatch time (`DispatchService` set this under `meta_data.webhook_payload`).

3) Saved payload lookup
   - The controller locates the most recent matching `DispatchLog` by `user_id`, contact phone, and `message.meta_data->evolution_template_id`.
   - If not found, it falls back to any recent log with a non-null `webhook_payload` for the same user/template.

4) Outbound body behavior
   - The system no longer auto-appends the entire meta context into the request body. Only your defined JSON (after interpolation) is sent.

### Frontend (User) UI Enhancements
1) Per-row HTTP Action section
   - Each row now shows a clean card with:
     - Row fields: `rowId`, `Title`, `Description`.
     - A bordered “HTTP Action” section with an Enable switch.
     - When enabled: `Method`, `URL`, `Headers (JSON)`, `Body (JSON)`.

2) Inline help & validation
   - JSON validation for Headers and Body with immediate error hints.
   - “Insert variable” dropdown to insert placeholders or `$path` directives without typing.
   - Mapping guide visible below Body with usage examples.

3) Persistence
   - Only enabled HTTP actions with a URL are saved to `row_actions`.

### Using Dynamic Mapping
- Examples for Body:
  - With placeholder:
    ```json
    { "waybill": "{{ webhook_payload.order_id }}", "case": "2" }
    ```
  - With default:
    ```json
    { "note": "{{ webhook_payload.note || none }}" }
    ```
  - With `$path` directive (node replaced by resolved value):
    ```json
    { "orderId": { "$path": "webhook_payload.order_id" } }
    ```

### Available Context Keys
- `selectedRowId`, `sender`, `customer`, `instance`, `gateway_id`, `user_id`, `raw` (full incoming body), `webhook_payload` (saved dispatch payload if available).

### Error Handling
- If a placeholder path does not resolve and no default is provided, the value becomes `null`.
- If `webhook_payload` is not found, placeholders under that key resolve to `null` (unless a default is provided).

### Backward Compatibility
- Existing Evolution sending and previously defined templates remain unaffected.
- Headers/Body left blank or without placeholders behave as before.

### Testing Checklist
- Create/edit Evolution template (type `list_buttons`) and configure HTTP actions:
  - Placeholder injection: `{{ webhook_payload.order_id }}` resolves to expected value.
  - `$path` directive replaces the node with the resolved primitive/object.
  - JSON validation hints show on malformed JSON.
  - Enable switch gating: disabled actions are not saved/executed.
- Webhook end-to-end:
  - Selection on a list button triggers HTTP request with interpolated values.
  - When no saved `webhook_payload` exists, placeholders resolve gracefully.

### Notes
- `webhook_payload` source is set during dispatch via `DispatchService` when called by the integration webhook flow.
- Contact matching uses the end-customer phone (`data.key.remoteJid`) for higher accuracy.


