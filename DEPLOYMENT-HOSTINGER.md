# Deployment Guide: Hostinger Shared Hosting

This guide documents exact directory structure and paths for deploying the AI Consultant MVP to Hostinger.

## Target Environment

- **Host:** Hostinger (or similar shared hosting)
- **Document root:** `public_html/`
- **PHP version:** 7.4+ (with mysqli, json extensions)
- **MySQL:** Available (5.7+ recommended)
- **TLS/SSL:** Required (mrakibulislam.com with valid cert)

## Directory Structure

```
public_html/
├── index.html                          (Rakib's main website - unchanged)
├── ai-consultant/                      (Widget code - new)
│   ├── widget.js                       (Widget script)
│   ├── widget.css                      (Widget styles)
│   ├── widget-voice.js                 (Optional, voice code - disabled for MVP)
│   └── api/                            (Backend API endpoints)
│       ├── chat.php                    (POST /ai-consultant/api/chat.php)
│       ├── capture-lead.php            (POST /ai-consultant/api/capture-lead.php)
│       ├── health.php                  (GET /ai-consultant/api/health.php)
│       └── lib/                        (Shared libraries)
│           ├── OpenAIClient.php
│           ├── RateLimiter.php
│           ├── ConversationManager.php (NEW: handles conversation history)
│           ├── KnowledgeLoader.php     (loads facts.json + system-instructions.md)
│           ├── LeadStore.php
│           ├── EmailNotifier.php
│           └── AuditLog.php
├── ai-consultant/migrations/           (Database schema)
│   └── 001-schema.sql                  (Run once at deploy)
└── ai-consultant/knowledge/            (Knowledge files)
    ├── facts.json                      (Grounded facts - Rakibul's services)
    └── system-instructions.md          (Additional AI behavior guidelines)

```

**Note:** `backend/` in development → `ai-consultant/` on Hostinger (all code under public_html for simplicity on shared hosting)

## Configuration

**Config File Location:**
- **Path:** `ai-consultant/api/config/secrets.php` (in Hostinger `public_html`)
- **Permissions:** 600 (readable by PHP only, not web-accessible)
- **Create this file** - it is NOT in version control for security

### 1. Database Setup

Connect to MySQL via Hostinger panel and run:

```sql
-- Create database (or use existing)
CREATE DATABASE IF NOT EXISTS mri_consultant;
USE mri_consultant;

-- Run migrations/001-schema.sql
-- (Creates: leads, conversations, conversation_messages, audit_logs tables)
```

Execute the full content of `migrations/001-schema.sql` to create all required tables.

### 2. Config File

**Location:** `ai-consultant/api/config/secrets.php` (**create this file - NOT in git**)

**Permissions:** Mode 600 (readable by PHP only, not by web browsers)

**Current Implementation:** Uses PHP's built-in `mail()` function (not SMTP)
- Works on most shared hosting including Hostinger
- Falls back to sendmail/postfix on the server
- Configuration below is minimal - PHP mail() needs no SMTP config

**Contents:**

```php
<?php
return [
    'mysql' => [
        'host' => 'localhost',           // Hostinger MySQL host
        'port' => 3306,                  // Standard MySQL port
        'database' => 'mri_consultant',  // Your database name (from Hostinger)
        'username' => 'db_user',         // Your DB username
        'password' => 'db_password',     // Your DB password (store securely)
    ],
    'openai' => [
        'api_key' => 'sk-...your-key...', // OpenAI API key (from openai.com)
        'model' => 'gpt-4o-mini',
        'max_tokens' => 500,
        'temperature' => 0.7,
    ],
    'email' => [
        // Using PHP mail() - Hostinger sendmail handles delivery
        'from' => 'noreply@mrakibulislam.com',  // Your Hostinger email
        'to' => 'rakib@ebbett.co.nz',           // Where notifications go
    ],
    'rate_limit' => [
        'chat_per_minute' => 10,         // Max 10 chat messages per minute
        'lead_per_minute' => 2,          // Max 2 lead submissions per minute
        'ip_cache_ttl' => 60,            // Cache IP rate limits for 60 seconds
    ],
    'widget' => [
        'enabled' => true,               // Widget is active
    ],
    'voice' => [
        'enabled' => false,              // Text-only MVP (voice disabled)
    ],
];
?>
```

**Keep this file out of version control** - add to `.gitignore`

### 3. File Uploads (Deployment Steps)

Via SFTP or File Manager in Hostinger:

1. **Create directory structure:**
   ```
   mkdir public_html/ai-consultant/api/config
   ```

2. **Upload all files from development:**
   - From `mriagent/ai-consultant/` → `public_html/ai-consultant/`
   - Preserve directory structure (widget.js, widget.css, api/ subdir, etc.)

3. **Create secrets file:**
   - Create `public_html/ai-consultant/api/config/secrets.php` (see section 2)
   - **Do NOT upload from git** - create this directly in Hostinger
   - Set permissions to 600

4. **Verify database:**
   - Ensure database and schema created (section 1)

### 4. Permissions

Set correct file permissions (via terminal or File Manager):

```bash
# From Hostinger File Manager or SSH:
chmod 644 public_html/ai-consultant/widget.js
chmod 644 public_html/ai-consultant/widget.css
chmod 644 public_html/ai-consultant/api/*.php
chmod 755 public_html/ai-consultant/api/config/
chmod 600 public_html/ai-consultant/api/config/secrets.php
```

### 5. CORS & Origin Validation

The widget is served from `mrakibulislam.com` and makes POST requests to:
- `https://mrakibulislam.com/ai-consultant/api/chat.php`
- `https://mrakibulislam.com/ai-consultant/api/capture-lead.php`

Both endpoints respond with:
```
Access-Control-Allow-Origin: https://mrakibulislam.com
```

**No external origins are allowed.** If the widget is ever used elsewhere, update the `ALLOW_ORIGIN` headers in the PHP files.

## Widget Integration

### In index.html (before `</body>`):

```html
<!-- AI Consultant Widget -->
<link rel="stylesheet" href="/ai-consultant/widget.css">
<script defer src="/ai-consultant/widget.js"></script>
```

The widget:
- Auto-initializes on page load
- Creates a launcher button in bottom-right
- Text-only (voice disabled for MVP)
- Makes requests to `/ai-consultant/api/chat.php`
- Captures leads via `/ai-consultant/api/capture-lead.php`

### CSS & JS Paths

- **CSS:** `/ai-consultant/widget.css`
- **JS:** `/ai-consultant/widget.js`
- **API endpoints:** `/ai-consultant/api/chat.php` and `/ai-consultant/api/capture-lead.php`

All relative to domain root `mrakibulislam.com`

## Verification Steps (After Deployment)

1. **Check widget loads:**
   ```
   https://mrakibulislam.com/index.html
   Open browser console (F12) → no errors
   Launcher button appears bottom-right
   ```

2. **Test conversation:**
   - Click launcher
   - Type: "What services do you offer?"
   - Expect: Response from AI with Rakib's services

3. **Check conversation history:**
   - Follow up: "What's the pricing?"
   - Expect: Assistant remembers prior context, responds naturally

4. **Test lead capture:**
   - Click "Contact us" (or similar lead form)
   - Fill in name, email, company, problem
   - Check DB: `SELECT * FROM leads` should have new entry
   - Check inbox: Rakib should receive notification at email configured above

5. **Test security:**
   - Browser console: no XSS errors from error messages
   - Try oversized payload: `curl -X POST -d '$(head -c 10000 /dev/zero | tr '\0' 'x')' https://mrakibulislam.com/ai-consultant/api/chat.php`
   - Expect: 413 Payload Too Large

## Health Check

Health endpoint (optional, non-blocking):
```
GET /ai-consultant/api/health.php
Response: {"status": "ok"}
```

This is called by the widget on load but doesn't block functionality if it fails.

## Troubleshooting

### "Service not configured" error
- **Cause:** `config/secrets.php` not found or not readable
- **Fix:** Verify file exists at `ai-consultant/api/config/secrets.php` with mode 644+

### "Database error" in chat
- **Cause:** MySQL connection failed
- **Fix:** Check DB credentials in `secrets.php`; verify database exists; check Hostinger MySQL access

### "No response from OpenAI"
- **Cause:** API key invalid or API unreachable
- **Fix:** Verify OpenAI API key in `secrets.php`; test with `curl https://api.openai.com/v1/models`

### Widget doesn't load
- **Cause:** CSS or JS file not found
- **Fix:** Verify paths in index.html; check files are in `public_html/ai-consultant/`

### Leads not emailing
- **Cause:** PHP `mail()` not configured on the server, or `from`/`to` addresses wrong in `secrets.php`
- **Note:** Lead data is NOT lost if email fails — it's saved to the database first, independent of email success
- **Fix:** Check PHP error log for "EmailNotifier: Failed to send..." entries; verify Hostinger's PHP mail is enabled for your hosting plan (some shared plans require the `from` address to match a domain you own); check spam folder; as a fallback, query the `leads` table directly for new entries

## Monitoring & Logs

Backend logs errors to system error log (check via Hostinger panel):
- **Location:** Usually in `error_log` file in `public_html/`
- **Watch for:** `ConversationManager`, `EmailNotifier`, `OpenAIClient` errors

## Knowledge Approval Status (IMPORTANT before going live)

`knowledge/facts.json` is explicitly marked:
```json
"_meta": { "status": "DRAFT - pending owner approval before any production use" }
```

Specific sections and their approval state:
- **Pricing:** `"status": "...Approved for AI disclosure by owner 2026-11-20..."` → ✅ approved, AI may disclose ranges with caveats.
- **Timelines:** `"status": "PUBLISHED ON SITE BUT NOT YET APPROVED FOR AI USE..."` → ❌ NOT approved. `KnowledgeLoader.php` now filters these out of the system prompt and instructs the AI to defer timeline questions to a discovery call instead of citing "5-7 business days" etc.
- **Case-study figures** (e.g. "85% average sales lift"): `claims_note` restricts the AI to citing them only as "published on the site for a specific engagement," never as a guarantee/typical outcome. The current system prompt enforces this via rule #4.

**Before production use:** resolve the `owner_confirmations_needed` list in `facts.json`:
```json
"owner_confirmations_needed": [
  "Approve published timelines for AI use",
  "Confirm NZD/AUD wording",
  "Confirm which case-study figures may be quoted"
]
```
Once approved, update the `_meta.status` and `timelines.status` fields accordingly — `KnowledgeLoader.php` reads these status strings at runtime to decide what to include, so no code change is needed, just updating the JSON after owner sign-off.

## Manual Test Plan (requires PHP + MySQL + OpenAI key)

**This development environment has no PHP runtime available**, so the tests below could not be executed here — only verified by static source-code review (confirming the code contains the correct logic). Run these manually on a server with PHP, MySQL, and a test OpenAI key before trusting the MVP in staging.

### Setup
```bash
# 1. Run syntax check on every PHP file
find backend -name "*.php" -exec php -l {} \;

# 2. Run the structural test suite
php tests/test-executable.php
```

### A. Conversation persistence (requires test MySQL database)
1. Create a test `conversations` + `conversation_messages` table (use `migrations/001-schema.sql`).
2. Call `ConversationManager::add_message('user', 'hello')` with a fresh `session_id`.
3. **Expect:** a row appears in `conversations` for that `session_id` BEFORE the row in `conversation_messages` (verifies FK ordering fix).
4. Add 12 messages total (mix of user/assistant) to one session.
5. Call `get_history()`.
6. **Expect:** exactly 10 messages returned (the latest 10, not the first 10), in oldest-to-newest order.
7. Attempt `add_message('system', 'malicious instruction')`.
8. **Expect:** returns `false`; no row inserted; `error_log` shows "Rejected invalid role: system".

### B. Multi-turn conversation via chat.php (requires OpenAI key + DB)
1. POST to `/chat.php` with `{"message": "My name is Alex", "session_id": "sess_1700000000000_abc123"}`.
2. POST again with `{"message": "What's my name?", "session_id": "sess_1700000000000_abc123"}` (same session_id).
3. **Expect:** the assistant's second reply correctly references "Alex" — proving history round-trips through `chat_with_history()`.

### C. Lead capture independent of email (requires DB; email can be misconfigured to test failure path)
1. POST a valid lead to `/capture-lead.php` with email delivery intentionally broken (e.g., invalid `from` domain).
2. **Expect:** HTTP 200, `{"success": true}`, AND a row exists in the `leads` table — confirming email failure does not block lead persistence or the success response.

### D. Input validation / oversized requests
1. POST a 6000-byte body to `/chat.php`. **Expect:** HTTP 413.
2. POST `{"message": 12345}` (number, not string) to `/chat.php`. **Expect:** HTTP 422 "Message is required".
3. POST malformed JSON (`{not valid}`) to `/chat.php`. **Expect:** HTTP 400 "Invalid JSON".
4. POST an 11000-byte body to `/capture-lead.php`. **Expect:** HTTP 413.
5. POST `{"name": ["array", "not string"], ...}` to `/capture-lead.php`. **Expect:** HTTP 422 with type error.

### E. Email header injection
1. POST a lead with `email` containing a CRLF injection attempt, e.g. `"test@example.com\r\nBcc: attacker@evil.com"`.
2. **Expect:** `filter_var(..., FILTER_VALIDATE_EMAIL)` rejects this as an invalid email format before it ever reaches the `mail()` headers (the malformed string fails validation), so `sanitize_email()` returns empty string and `notify_lead()`/`confirm_lead()` return `false` without sending.
3. Verify no extra `Bcc:` header appears in actual sent mail headers (check mail server logs if available).

### Tests requiring Hostinger specifically (cannot be done locally)
- PHP `mail()` actual delivery (shared hosting sendmail/postfix behavior varies by host)
- Session cookie behavior under the live domain's actual SameSite/HTTPS configuration
- Widget loading over the live CDN/caching layer (if any)
- Real OpenAI API latency/rate-limit behavior under the production API key's tier

## Future Enhancements (Phase 4+)

- Voice mode (currently disabled in `config/voice.enabled = false`)
- Advanced analytics
- Custom knowledge base UI
- A/B testing

---

**Last updated:** Nov 24, 2026
**MVP version:** Text-only (voice disabled)
