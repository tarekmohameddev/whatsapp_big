## Pipelines & Integrations – Webhook Variables Mapping for Templates

### Overview
This feature lets you use values from an incoming webhook payload as variables inside WhatsApp templates for both:
- Meta Cloud Official (Cloud API)
- Evolution API (including List Buttons)

You can define variables in the Integration UI by giving each variable a name and a JSON dot path in the payload. These variables can be referenced with named placeholders like `{{message}}` in Evolution templates and free-text bodies, and mapped to positional placeholders in Cloud templates.

### What’s Supported
- Named placeholders: Use `{{variableName}}` in:
  - Evolution WhatsApp template payload fields (e.g., titles, descriptions, section items)
  - Free-text message bodies
- Defaults and per-Rule variables:
  - Defaults apply when no rules match and as a base when rules do match
  - Rule variables override Defaults by name
- Fallback (no config needed): If you don’t configure variables at all, top-level scalar keys from the webhook payload are auto-exposed as variables (e.g., `message`, `order_id`).
- Cloud API mapping: Variables are mapped in order to `body_placeholder_1..N`, which the existing Cloud template builder consumes when generating the body parameters. Header/button placeholders continue to work via the existing builder flow.

### Configure in the UI
1) Create or Edit an Integration
   - Set Phone Number JSON Path (dot path to recipient in the incoming JSON)
   - Optionally select Allowed Methods/Gateways
   - Choose Defaults: Method, Gateway, Template (for Cloud or Evolution)

2) Defaults → Variables Mapping (optional)
   - Add rows with:
     - Variable Name (e.g., `message`, `order_id`)
     - JSON Path (e.g., `message`, `order_id`, `order.total.amount`)

3) Rules (optional)
   - Define conditions (Match Path, Operator, Value)
   - Select Action Method/Gateways/Template
   - Variables Mapping (Rule) to override or add variables for this rule

4) Save the Integration

### How It Works
1) Resolution
   - On webhook, the system merges Defaults and all matching Rules (ascending priority)
   - Variables are merged by name; later rules override earlier/default values

2) Variable Extraction
   - Each variable is resolved from the webhook payload using its dot path
   - Non-scalar values are JSON-encoded; missing values resolve to an empty string
   - If you configured no variables, top-level scalar keys are auto-exposed

3) Injection into Dispatch
   - Variables are added to the dispatch request and saved into `Message.meta_data.variables`
   - Free-text `message_body` has `{{var}}` replaced and any unknown placeholders removed

4) Channel-specific Behavior
   - Cloud API:
     - Variables are mapped by order to `body_placeholder_1..N` for the body component
     - Header and button placeholders continue to be handled by the Cloud template builder
   - Evolution API:
     - The stored Evolution template payload is loaded and recursively interpolated
     - All `{{var}}` found anywhere in the payload (including list titles/descriptions/sections) are replaced
     - Unknown placeholders are removed

### Example
Incoming payload:
```json
{
  "client_phone_1": "+201155522984",
  "status_id": "going",
  "message": "لقد قام مندوب كويك كونتيكت بمحاولة اتصال بك",
  "branch_id": "222",
  "order_id": "QCK492732"
}
```

Integration setup:
- Phone Path: `client_phone_1`
- Defaults → Variables (either of these approaches works):
  - Configure rows:
    - name: `message`, path: `message`
    - name: `order_id`, path: `order_id`
  - Or rely on the fallback (no rows) since these are top-level keys

Evolution template (List Buttons) includes in a field (e.g., description):
```
cccccccc {{message}}
```

Result:
```
cccccccc لقد قام مندوب كويك كونتيكت بمحاولة اتصال بك
```

### Security
- The webhook endpoint requires the `X-Webhook-Secret` header to match the Integration’s secret
- Only scalar values are injected as plain text; objects/arrays are JSON-encoded

### Troubleshooting
- Placeholder still visible (e.g., `{{message}}`):
  - Verify the template field actually contains `{{message}}`
  - Ensure the Integration selected Evolution method and the correct Evolution template
  - If using manual mapping, verify the variable name matches the placeholder and the path is correct
  - If no mappings are configured, confirm the placeholder name matches a top-level scalar key in the payload

### Endpoint
- POST `api/integrations/{uid}`
  - Header: `X-Webhook-Secret: <secret>`
  - Body: arbitrary JSON, e.g. the example payload above


