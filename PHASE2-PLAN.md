# Phase 2: Secure backend foundation — Implementation plan

**Goal:** build a secure PHP + MySQL backend for the AI consultant. No deployment yet. No code is committed.

**Constraints:**
- Hostinger shared plan, latest PHP, MySQL available, no built-in secret storage.
- Deployment: git push to Hostinger.
- SMTP credential currently in source (blocker: must rotate before this is deployed).

**Strategy:**
- Secrets (OpenAI API key, SMTP password) live in a `config/secrets.php` file outside the web root; never tracked in git.
- Each Hostinger developer creates `config/secrets.php` once by hand after checking out the repo.
- API endpoints in `ai-consultant/api/` validate requests, check rates, call OpenAI server-side, and return sanitised responses.
- MySQL stores leads (with consent), conversation summaries (optional), and audit logs.
- Error responses never leak secrets, keys or internal paths.

## Files to create

```
mri/
├── ai-consultant/
│   ├── api/
│   │   ├── chat.php          # POST /ai-consultant/api/chat.php — text message
│   │   ├── voice-token.php   # POST /ai-consultant/api/voice-token.php — short-lived Realtime cred (Phase 4)
│   │   ├── capture-lead.php  # POST /ai-consultant/api/capture-lead.php — save lead with consent
│   │   ├── health.php        # GET /ai-consultant/api/health.php — service status
│   │   └── middleware/
│   │       ├── auth.php      # API key check
│   │       ├── rate-limit.php # IP + session rate limiting
│   │       └── cors.php      # Origin validation
│   ├── widget.js             # Launcher and chat UI (Phase 3)
│   ├── widget.css            # Styles (Phase 3)
│   └── README.md
├── config/
│   ├── example-secrets.php   # Tracked; shows structure, not values
│   └── secrets.php           # NOT tracked; created once by hand on each host
├── lib/
│   ├── OpenAIClient.php      # Calls OpenAI API
│   ├── LeadStore.php         # MySQL lead capture
│   ├── AuditLog.php          # Logging without exposing secrets
│   └── RateLimiter.php       # IP/session-based rate limiting
└── migrations/
    └── 001-schema.sql        # MySQL tables: leads, conversations (optional), audit logs
```

## Endpoints (Phase 2)

### POST `/ai-consultant/api/chat.php`
Request:
```json
{
  "message": "What services do you offer?",
  "session_id": "abc123...",
  "consent_to_store": false
}
```
Response (success):
```json
{
  "success": true,
  "reply": "I offer Shopify, Zendesk, HubSpot and AI automation...",
  "session_id": "abc123..."
}
```
Response (error):
```json
{
  "success": false,
  "error": "Service temporarily unavailable. Please email hello@mrakibulislam.com."
}
```

Validation:
- Max 1000 chars per message.
- Rate limit: 10 messages per minute per IP.
- CORS: origin must match mrakibulislam.com.
- No secrets in error messages.

### POST `/ai-consultant/api/capture-lead.php`
Request:
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "company": "Acme Inc",
  "problem": "Need HubSpot implementation",
  "consent": true
}
```
Response (success):
```json
{
  "success": true,
  "message": "Thank you. We'll be in touch."
}
```

Validation:
- Honeypot: form includes hidden `website_hp` field (if filled, silently accept but don't save).
- Consent flag must be true; refuse if false.
- Email must be valid.
- Rate limit: 1 lead per minute per IP.

MySQL:
```sql
leads (
  id, name, email, company, problem, 
  created_at, consent_at, deleted_at, notes
)
```
Retention: 12 months from last contact; soft delete (`deleted_at`).

### GET `/ai-consultant/api/health.php`
Returns:
```json
{
  "status": "ok",
  "openai": "ok" | "unreachable",
  "mysql": "ok" | "unreachable",
  "timestamp": "2026-11-20T22:50:00Z"
}
```
No secrets exposed. Used for monitoring.

## Security checklist

- [ ] All secrets in `config/secrets.php` (outside web root, not tracked).
- [ ] `.gitignore` excludes `config/secrets.php`.
- [ ] Every endpoint validates input (type, length, format).
- [ ] Rate limiting per IP and session.
- [ ] CORS locked to `mrakibulislam.com`.
- [ ] Error responses never mention keys, paths, database errors.
- [ ] No raw email logging; audit log hashes emails.
- [ ] MySQL queries use prepared statements (no SQL injection).
- [ ] Honeypot on lead capture.
- [ ] HTTPS enforced at Hostinger level.
- [ ] Example config file shows structure without exposing values.
- [ ] Session-based rate limit can't be bypassed by dropping cookies (IP limit as fallback).

## Tests (before Phase 3)

1. Valid message → valid response (OpenAI API mocked).
2. Missing message → error.
3. 11 messages in 60 sec → 11th rejected (rate limit).
4. POST to non-mrakibulislam.com origin → 403.
5. Lead without consent → rejected.
6. Lead with invalid email → rejected.
7. Lead honeypot filled → silently skipped (not saved).
8. Health check with OpenAI unreachable → returns `"openai": "unreachable"`.
9. Error response has no stack trace, path or key hint.
10. Audit log captures lead attempts without storing email plaintext.

## Exit criteria

- Endpoints created and tested locally.
- No secrets in git history.
- Example secrets file shows the structure.
- Error handling verified.
- Rate limiting tested.
- Ready for Phase 3 (text chat widget).
