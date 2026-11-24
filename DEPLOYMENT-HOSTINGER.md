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

**Location:** `ai-consultant/api/config/secrets.php` (create this file)

**Permissions:** Mode 600 (readable by PHP only)

**Contents:**

```php
<?php
return [
    'mysql' => [
        'host' => 'localhost',           // Hostinger MySQL host
        'port' => 3306,                  // Standard MySQL port
        'database' => 'mri_consultant',  // Your database name
        'username' => 'db_user',         // Your DB username
        'password' => 'db_password',     // Your DB password (store securely)
    ],
    'openai' => [
        'api_key' => 'sk-...your-key...', // OpenAI API key
        'model' => 'gpt-4o-mini',
        'max_tokens' => 500,
        'temperature' => 0.7,
    ],
    'email' => [
        'from' => 'notifications@mrakibulislam.com',
        'to' => 'rakib@ebbett.co.nz',    // Where lead notifications go
        'smtp_host' => 'mail.hostinger.com',  // Hostinger SMTP
        'smtp_port' => 465,              // SSL port
        'smtp_user' => 'notifications@mrakibulislam.com',
        'smtp_pass' => '...',            // App password
    ],
    'rate_limit' => [
        'chat_per_minute' => 10,         // Max 10 messages per minute
        'lead_per_minute' => 2,          // Max 2 leads per minute
        'ip_cache_ttl' => 60,            // Cache for 60 seconds
    ],
    'widget' => [
        'enabled' => true,               // Widget is active
    ],
    'voice' => [
        'enabled' => false,              // Text-only MVP
    ],
];
?>
```

**Keep this file out of version control** (add to .gitignore)

### 3. File Uploads (Deployment Steps)

Via SFTP or File Manager in Hostinger:

1. Create directory: `public_html/ai-consultant/`
2. Upload all files from `mriagent/ai-consultant/` preserving structure
3. Create directory: `public_html/ai-consultant/api/config/`
4. Create `secrets.php` (see section 2 above) in that config directory
5. Ensure database is set up (see section 1)

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
- **Cause:** SMTP credentials wrong or email service down
- **Fix:** Test SMTP: `telnet mail.hostinger.com 465`; check Hostinger email settings

## Monitoring & Logs

Backend logs errors to system error log (check via Hostinger panel):
- **Location:** Usually in `error_log` file in `public_html/`
- **Watch for:** `ConversationManager`, `EmailNotifier`, `OpenAIClient` errors

## Future Enhancements (Phase 4+)

- Voice mode (currently disabled in `config/voice.enabled = false`)
- Advanced analytics
- Custom knowledge base UI
- A/B testing

---

**Last updated:** Nov 24, 2026
**MVP version:** Text-only (voice disabled)
