# WhatsApp Evolution API Templates

## Overview
This document details the Evolution API Templates feature added to the user portal. It enables users to create, edit, and delete reusable WhatsApp message templates (specific to Evolution API) that store the message payload JSON (without the recipient number). These templates can be reused later instead of retyping message bodies each time.

Supported template types and payloads match the Evolution API body structures:
- simple_txt
- image (with optional caption and filename)
- poll
- list_buttons

## Feature Components

### Database
- Migration: `src/database/migrations/2025_08_27_000100_create_evolution_whatsapp_templates_table.php`
- Table: `evolution_whatsapp_templates`
  - `id` bigint, auto-increment
  - `uid` string(100), unique
  - `user_id` bigint, nullable, indexed
  - `name` varchar(255)
  - `type` enum: [`simple_txt`, `image`, `poll`, `list_buttons`]
  - `payload` json (message body without `number`)
  - `status` enum: [`active`, `inactive`] default `active`
  - Timestamps

Notes:
- A foreign key to `users(id)` is optional. If desired, add it back with `->foreign('user_id')->references('id')->on('users')->nullOnDelete()`.

### Model
- Path: `src/app/Models/EvolutionWhatsappTemplate.php`
- Casts:
  - `payload` → array
  - `status` → `App\Enums\Common\Status`
  - `type` → `App\Enums\System\EvolutionWhatsappTemplateTypeEnum`
- Auto-generates `uid` on create via `str_unique()`

### Enum
- Path: `src/app/Enums/System/EvolutionWhatsappTemplateTypeEnum.php`
- Values:
  - `SIMPLE_TXT = 'simple_txt'`
  - `IMAGE = 'image'`
  - `POLL = 'poll'`
  - `LIST_BUTTONS = 'list_buttons'`
- Helpers: `label()` and `values()`

### Controller (User)
- Path: `src/app/Http/Controllers/User/Template/WhatsappEvolutionTemplateController.php`
- Actions:
  - `index()` – list user templates (paginated)
  - `create()` – show create form
  - `store(Request)` – validate and save
  - `edit(string $uid)` – show edit form
  - `update(Request, string $uid)` – validate and update
  - `destroy(string $uid)` – delete template
- Validation is context-aware based on `type` and ensures each payload matches the required structure.
- Payload is built server-side from validated inputs and stored under `payload`.

### Routes (User)
- File: `src/routes/user.php`
- Group: `user.template.whatsapp.evolution.*`
  - `GET  /user/template/whatsapp/evolution` → `index` (name: `user.template.whatsapp.evolution.index`)
  - `GET  /user/template/whatsapp/evolution/create` → `create` (name: `user.template.whatsapp.evolution.create`)
  - `POST /user/template/whatsapp/evolution` → `store` (name: `user.template.whatsapp.evolution.store`)
  - `GET  /user/template/whatsapp/evolution/{uid}/edit` → `edit` (name: `user.template.whatsapp.evolution.edit`)
  - `PATCH /user/template/whatsapp/evolution/{uid}` → `update` (name: `user.template.whatsapp.evolution.update`)
  - `DELETE /user/template/whatsapp/evolution/{uid}` → `destroy` (name: `user.template.whatsapp.evolution.destroy`)

### Views (User)
- Directory: `src/resources/views/user/template/whatsapp/evolution/`
  - `index.blade.php` – list with actions
  - `create.blade.php` – create form with dynamic fields per `type`
  - `edit.blade.php` – edit form with dynamic fields and prefill from `payload`

Frontend Notes:
- Scripts are pushed to the correct stack `@push('script-push')` to ensure they load (the layout renders this stack).
- Dynamic fields switch based on `type` (simple text, image, poll, list buttons).
- For list buttons, a small UI builder is provided for sections/rows.
- A hidden `sections_json` field carries the serialized sections array; on submit it is converted to `sections[...][rows][...]` inputs for Laravel validation, and the JSON field is removed.

### Sidebar (User)
- File: `src/resources/views/user/partials/sidebar.blade.php`
- Under Templates → WhatsApp is now a dropdown with two entries:
  - Official (Cloud API) → `user.template.index` with channel `whatsapp`
  - Evolution API → `user.template.whatsapp.evolution.index`

## Payload Formats (stored in `payload` column)

- simple_txt
```json
{ "text": "Your message text" }
```

- image with text
```json
{ "mediatype": "image", "caption": "Here’s your image!", "fileName": "file.jpg", "media": "https://example.com/file.jpg" }
```

- poll
```json
{ "name": "Poll title", "selectableCount": 1, "values": ["Option 1", "Option 2"] }
```

- list_buttons
```json
{
  "type": "list",
  "title": "Available Services",
  "description": "Please select one from the list below 👇",
  "buttonText": "View Options",
  "footerText": "Footer",
  "sections": [
    {
      "title": "Plans",
      "rows": [
        { "rowId": "plan_basic", "title": "Basic Plan", "description": "Starter features" },
        { "rowId": "plan_premium", "title": "Premium Plan", "description": "Advanced features" }
      ]
    }
  ]
}
```

## How To Use
1) Go to Templates → WhatsApp → Evolution API
2) Click Create
3) Enter name
4) Choose a type (simple text/image/poll/list buttons)
5) Fill out the dynamic fields; for list buttons, add sections and rows
6) Save
7) Later, select these templates when sending through Evolution API flows (future wiring, see Next Steps)

## Integration Notes
- These templates are stored and ready for reuse. The sending forms for Evolution API can load a selected template and inject the `payload` (number will be supplied at send time).
- As of this feature, the CRUD and storage are implemented; consumption during send flows can be added as an enhancement.

## Limitations / Next Steps
- Wire template selection into Evolution single send and campaign UIs to auto-fill the message body.
- Add a “Preview JSON” button in create/edit views.
- Optional: status toggle and bulk actions on index.
- Optional: add strict FK to users.

## Testing Checklist
- Create a template of each type and verify payload stored correctly without `number`.
- Edit a template and confirm prefilled values and updates.
- Delete a template and confirm removal from index.
- Sidebar: verify WhatsApp → Official vs Evolution entries.
- Validate dynamic fields appear immediately on type change (ensure `script-push` stack loads).

## Migration & Rollback
- Run migrations: `php artisan migrate`
- Rollback (last batch): `php artisan migrate:rollback`

## Changelog
- 2025-08-27: Initial implementation of Evolution API Templates (DB, model, enum, controller, routes, views, sidebar).
