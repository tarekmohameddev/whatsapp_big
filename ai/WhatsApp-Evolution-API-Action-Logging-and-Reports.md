## WhatsApp Evolution API – Action Logging & Reports

### Overview
Adds persistent logging and user-facing reports for:
- Button clicks on Evolution list templates
- Outbound HTTP actions dispatched in response to those clicks (request/response/error)

Complements the existing Evolution webhook handling and dynamic mapping features.

### Data Model
- evolution_button_clicks
  - id, uid, user_id, template_id, gateway_id?, selected_row_id, row_title?, row_description?, sender?, customer?, raw_payload?, timestamps
  - Indexes: ebc_user_tpl_row_idx(user_id, template_id, selected_row_id), ebc_user_created_idx(user_id, created_at)

- evolution_http_action_logs
  - id, uid, user_id, template_id, gateway_id?, selected_row_id, method, url, request_headers? (json), request_body? (json), response_status?, response_body? (longtext), sender?, customer?, context_meta? (json), error_message?, timestamps
  - Indexes: ehal_user_tpl_row_idx(user_id, template_id, selected_row_id), ehal_user_created_idx(user_id, created_at)

Migrations:
- 2025_09_21_000500_create_evolution_button_clicks_table.php
- 2025_09_21_000600_create_evolution_http_action_logs_table.php

Note: Composite index names shortened for MySQL identifier limits.

### Controller Changes
File: src/app/Http/Controllers/Api/IncomingApi/EvolutionWebhookController.php
- Always logs a button click with matched row meta (title/description)
- Logs outbound HTTP action request/response or error
- Webhook response semantics unchanged

### Routes (User)
- GET /user/template/whatsapp/evolution/analytics/clicks → user.template.whatsapp.evolution.analytics.clicks
- GET /user/template/whatsapp/evolution/analytics/clicks-summary → user.template.whatsapp.evolution.analytics.clicks_summary
- GET /user/template/whatsapp/evolution/analytics/http-logs → user.template.whatsapp.evolution.analytics.http_logs

Registered in: src/routes/user.php

### Views
- src/resources/views/user/template/whatsapp/evolution/analytics/clicks.blade.php
- src/resources/views/user/template/whatsapp/evolution/analytics/clicks_summary.blade.php
- src/resources/views/user/template/whatsapp/evolution/analytics/http_logs.blade.php

All views follow the standard layout:
- main-body → container-fluid main-content → page-header
- table-filter with datePicker
- card → card-header → card-body → table-container

### Navigation (Sidebar)
Report → Evolution API Reports
- Button Clicks
- Clicks Summary
- HTTP Action Logs

File: src/resources/views/user/partials/sidebar.blade.php
- Templates menu excludes analytics routes for active state
- Report menu includes analytics routes and auto-expands

### Usage
1) Send an Evolution list template to a user
2) On row click, webhook logs a click and (if configured) dispatches HTTP action
3) Use reports to analyze clicks and HTTP outcomes

### Example Queries
Top rows by clicks (SQL):
```sql
SELECT selected_row_id, COALESCE(MAX(row_title), '') AS row_title, COUNT(*) AS clicks
FROM evolution_button_clicks
WHERE user_id = :user_id
GROUP BY selected_row_id
ORDER BY clicks DESC
LIMIT 10;
```

Recent HTTP errors (SQL):
```sql
SELECT *
FROM evolution_http_action_logs
WHERE user_id = :user_id AND (error_message IS NOT NULL OR response_status IS NULL)
ORDER BY id DESC
LIMIT 100;
```

### Testing Checklist
- Click triggers create rows in both tables (when action configured)
- Filters for date/method/status/template work
- Sidebar active/expanded state correct under Report → Evolution API Reports

### Troubleshooting
- Identifier too long: migrations use shortened index names; run only new migrations if tables already exist
- Misaligned pages: ensure views use standard layout and flatpickr init

### Future Enhancements
- CSV export per report
- Per-template drill-down
- Filters: sender/customer, gateway
- Retention policy for log tables

### Changelog
- 2025-09-21: Initial logging and reports feature


