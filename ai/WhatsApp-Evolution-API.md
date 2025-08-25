# WhatsApp Evolution API Integration

This document describes the Evolution API integration added to the system, the files changed, the sending flows it enables (single and campaign), and key challenges we addressed during implementation.

## Overview
- New WhatsApp gateway type: Evolution API
- Supports sendText via Evolution: POST {server}/message/sendText/{instance}
- Authentication via `apikey` header
- User and Admin can add/manage Evolution gateways (instance, server, token, delays)
- Single Send and Campaign flows updated to use Evolution gateways

## Key Features
- Add Evolution gateways in Admin and User portals
- Dispatch layer routes Evolution messages through a new sender
- Single send UI exposes Evolution as a sending method, with message body + media
- Campaign creation UI exposes Evolution as a sending method, with gateway selection
- Evolution is considered a “cloud-like” gateway for selection purposes but uses body-based (non-template) sending

## Backend Changes
- Enum
  - `App\\Enums\\System\\Gateway\\WhatsAppGatewayTypeEnum`: added `EVOLUTION`
- Sender
  - `App\\Http\\Utility\\SendWhatsapp`
    - `sendWithHandler(...)`: added branch for `EVOLUTION`
    - `sendEvolutionMessages(...)` implemented
      - URL: `{server}/message/sendText/{instance}`
      - Headers: `Content-Type: application/json`, `apikey: {token}`
      - Body: `{ "number": "<E164>", "text": "<message>" }`
- Gateway retrieval and filtering
  - `App\\Managers\\GatewayManager`
    - Treats `cloud_api=true` as including both `CLOUD` and `EVOLUTION`
  - `App\\Services\\System\\Communication\\GatewayService`
    - `loadLogs(...)` supports `EVOLUTION` and returns view data + required credentials
- Config
  - `config/setting.php`
    - Added `gateway_credentials.whatsapp.evolution.meta_data`: `instance`, `server`, `token`
- Access control
  - `App\\Http\\Middleware\\AccessMiddleware`
    - User routes under `gateway.whatsapp.evolution.*` allowed when WhatsApp is allowed in plan

## Controllers & Routes
- Admin
  - Routes: `admin.gateway.whatsapp.evolution.*`
  - Controller: `App\\Http\\Controllers\\Admin\\Communication\\Gateway\\WhatsappEvolutionApiController`
- User
  - Routes: `user.gateway.whatsapp.evolution.*`
  - Controller: `App\\Http\\Controllers\\User\\Communication\\Gateway\\WhatsappEvolutionApiController`

## Views (Admin)
- `resources/views/admin/gateway/index.blade.php`
  - Added “Whatsapp Evolution API” tab
- `resources/views/admin/gateway/whatsapp/evolution/index.blade.php`
  - List, add, update, delete Evolution gateways with required credentials + delay settings

## Views (User)
- `resources/views/user/gateway/index.blade.php`
  - Added “Whatsapp Evolution API” tab
- `resources/views/user/gateway/whatsapp/evolution/index.blade.php`
  - List, add, update, delete Evolution gateways
  - Added required delay fields (min/max per-message, delay-after count/duration, reset)

## Single Send (User)
- `resources/views/user/communication/whatsapp/create.blade.php`
  - Sending Method includes `Evolution API`
  - New Evolution gateway selector
  - Shows message body editor and media upload when Evolution is selected
- Frontend toggling
  - `assets/theme/global/js/whatsapp/whatsapp.js`
    - Handles `evolution_api` mode by showing message body (like device) and hiding template UI

## Campaign (User)
- `resources/views/user/communication/whatsapp/campaign/create.blade.php`
  - Sending Method includes `Evolution API`
  - New Evolution gateway selector
  - Gateway id wiring updated for Evolution method

## Dispatch Flow
- Evolution gateways are considered when the user chooses Cloud-like sending or explicitly selects an Evolution gateway
- Message body is posted to Evolution’s sendText endpoint

## Usage
1) Add gateway (Admin or User)
   - Go to Gateways → WhatsApp Evolution API → Add Evolution Gateway
   - Fill: Name, Instance, Server URL, Token, delays
2) Single Send
   - Communication → WhatsApp → Choose “Evolution API”
   - Select Evolution gateway
   - Write message (and optional media)
   - Send
3) Campaign
   - Communication → WhatsApp → Create Campaign → Choose “Evolution API”
   - Select Evolution gateway and proceed through steps

## Challenges & Resolutions
- Permission alignment
  - Issue: User Evolution routes blocked by plan middleware
  - Fix: Allowed `gateway.whatsapp.evolution.*` in `AccessMiddleware` when WhatsApp is allowed in plan
- Unified gateway selection
  - Requirement: Evolution should be selectable like cloud but not use templates
  - Approach: Treat Evolution as cloud-like in back-end filters but show body editor (no templates) in UI and JS
- Frontend toggling
  - Issue: Message body not shown for Evolution on single send
  - Fix: Split device UI block into selection vs message block and explicitly show message block for `evolution_api`
- Required fields in user gateway modal
  - Issue: User add/update forms missing delay settings causing validation errors
  - Fix: Added delays (min/max per message, delay-after, reset) and wired prefill on edit

## Testing Notes
- Verified gateway CRUD for Admin and User
- Verified single send with Evolution shows message editor and posts gateway id
- Verified campaign create with Evolution shows gateway selection and sets hidden `gateway_id`
- Verified send path hits `sendEvolutionMessages` and builds correct HTTP request

## Limitations / Next Steps
- Media support sends to device flow; if Evolution requires dedicated media endpoints, add a media branch
- Template-based Evolution (if desired) can be added later with dedicated template UI and send path
- Add admin/user test buttons to validate credentials via a ping to Evolution server

## API Reference (Evolution sendText)
- Method: POST
- URL: `{server}/message/sendText/{instance}`
- Headers: `Content-Type: application/json`, `apikey: <token>`
- Body: `{ "number": "+1234567890", "text": "Hello" }`

---
This document covers what we implemented and why, along with known constraints and follow-up opportunities.

