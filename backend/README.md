# AI Consultant Backend — Phase 2

Secure PHP + MySQL backend for the mrakibulislam.com AI consultant widget. No deployment yet.

## Setup

### 1. Database

On Hostinger, create a MySQL database:
- Database name: `mrakibulislam` (or choose your own)
- User: `mri_user` (or choose your own)
- Password: (strong password)

Then run:
```bash
mysql -h localhost -u mri_user -p mrakibulislam < migrations/001-schema.sql
```

### 2. Secrets

**Never track `config/secrets.php` in git.** It is listed in `.gitignore`.

Create it once on the server:

```bash
# Outside public_html, e.g. in your home folder
mkdir -p ~/config
cp backend/config/example-secrets.php ~/config/secrets.php
chmod 600 ~/config/secrets.php
```

Edit `~/config/secrets.php` with:
- OpenAI API key (get from https://platform.openai.com/api-keys)
- SMTP password (the one that needs to be rotated per the brief)
- MySQL credentials (from step 1)

**Important:** the API endpoints reach this file by going up out of the web root:
```php
require(__DIR__ . '/../../../config/secrets.php');
```

### 3. Deploy

Push the `mri` repo (which will include the backend in `ai-consultant/api/`):
```bash
git push
```

The PHP files in `ai-consultant/api/` are now live on your Hostinger domain.

## Endpoints

All endpoints are POST unless otherwise noted.

### `POST /ai-consultant/api/chat.php`

Request:
```json
{
  "message": "What services do you offer?",
  "consent_to_store": false
}
```

Response:
```json
{
  "success": true,
  "reply": "I offer Shopify, Zendesk, HubSpot...",
  "session_id": "..."
}
```

Limits:
- 10 messages per minute per IP/session.
- 1000 chars max per message.
- Origin: `mrakibulislam.com` only.

### `POST /ai-consultant/api/capture-lead.php`

Request:
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "company": "Acme Inc",
  "problem": "Need HubSpot",
  "consent": true
}
```

Response:
```json
{
  "success": true,
  "message": "Thank you. We'll be in touch"
}
```

Limits:
- 1 lead per minute per IP.
- Honeypot: a hidden `website_hp` field (if filled, silently skip).
- Consent flag must be true.

### `GET /ai-consultant/api/health.php`

Returns:
```json
{
  "status": "ok",
  "openai": "ok",
  "mysql": "ok",
  "timestamp": "2026-11-20T22:50:00Z"
}
```

No authentication; used for monitoring.

## Security checklist

- [x] Secrets outside web root, not in git
- [x] Rate limiting per IP + session
- [x] CORS locked to `mrakibulislam.com`
- [x] Input validation (length, format, type)
- [x] Error responses never expose keys, paths, or database errors
- [x] Honeypot on lead capture
- [x] Email hashing in audit logs
- [x] Prepared statements (no SQL injection)
- [x] Session-based rate limit with IP fallback

## Testing

(See `../PHASE2-PLAN.md` for the full test suite.)

Quick tests:
```bash
# Valid message
curl -X POST https://mrakibulislam.com/ai-consultant/api/chat.php \
  -H "Content-Type: application/json" \
  -d '{"message": "What services?", "consent_to_store": false}'

# Health check
curl https://mrakibulislam.com/ai-consultant/api/health.php

# Rate limit (send 11 messages in 60 sec; 11th should fail)
for i in {1..11}; do
  curl -X POST https://mrakibulislam.com/ai-consultant/api/chat.php \
    -H "Content-Type: application/json" \
    -d '{"message": "Test", "consent_to_store": false}'
  sleep 2
done
```

## Logs

- **Errors:** check `error_log` on the Hostinger server (ask support for the path).
- **Audit:** query the `audit_logs` table in MySQL to see who did what.

## Next phase

Phase 3: build the widget (`widget.js`, `widget.css`) that uses these endpoints to chat.
