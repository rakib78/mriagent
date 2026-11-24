# Implementation Status & Remaining Work

## Completed Fixes (Nov 24, 2026 - Round 2: Review Findings)

User independently reviewed Round 1 and found it had NOT been pushed to GitHub (local-only commits) and identified 6 further issues once pushed. All 6 are fixed in commits `[see latest SHA at bottom]`:

✅ **1. Conversation persistence correctness**
- `ConversationManager::ensure_conversation_exists()` now creates a `conversations` row before any `conversation_messages` insert, satisfying the FK constraint (previously would have thrown a PDOException on first message of a new session and silently failed)
- `get_history()` changed from `ORDER BY created_at ASC LIMIT 10` (wrong: returned the OLDEST 10 messages, i.e. the start of a long conversation) to `ORDER BY created_at DESC LIMIT 10` + `array_reverse()` (returns the LATEST 10, in chronological order)
- `add_message()` no longer accepts `'system'` as a role — only `'user'`/`'assistant'`. `get_history()` also filters out any non-user/assistant rows defensively on read
- All failure paths now `error_log()` explicitly and return `false` rather than silently continuing
- **Session ID bug found and fixed:** `chat.php` previously ignored the `session_id` the widget generates and sent, using PHP's native session cookie instead — a real redundancy/inconsistency. Now validates and prefers the client-supplied `session_id` (format-checked with regex), falling back to PHP's session only if missing/invalid. `widget.js` now syncs its stored session_id from the server's response.

✅ **2. Deployment configuration paths - CORRECTED**
- `chat.php` and `capture-lead.php` previously loaded config from `__DIR__ . '/../../../config/secrets.php'` (three levels up) while `DEPLOYMENT-HOSTINGER.md` instructed placing it at `ai-consultant/api/config/secrets.php` (same level) — these disagreed.
- Fixed both PHP files to use `__DIR__ . '/config/secrets.php'` (one level down from the API directory), matching the deployment guide exactly. Config now lives at `<api-dir>/config/secrets.php`, outside the directly-web-servable widget files, with instructions to set mode 600.

✅ **3. Email delivery documentation - CORRECTED**
- `EmailNotifier.php` uses PHP's built-in `mail()` — it never implemented SMTP.
- `DEPLOYMENT-HOSTINGER.md` previously listed `smtp_host`/`smtp_port`/`smtp_user`/`smtp_pass` config keys that were never read by any code — misleading. Removed; replaced with accurate `mail()`-based config (`from`/`to` only) and a troubleshooting note that lead data is never lost on email failure.
- Also fixed: `EmailNotifier` constructor was reading `$config['site']['notify_email']`, but `secrets.php` defines `$config['email']['from']`/`$config['email']['to']` — a config-path mismatch that would have made every email silently fail. Now reads the correct keys.

✅ **4. Knowledge approval controls - FIXED**
- `facts.json` marks itself `"DRAFT - pending owner approval"`, and `timelines.status` explicitly says `"NOT YET APPROVED FOR AI USE"`. The previous `KnowledgeLoader` ignored this and would have put timeline estimates into the system prompt regardless.
- `KnowledgeLoader::build_system_prompt()` now checks `pricing.status` for `"Approved"` before including price ranges, and checks `timelines.status` for `"NOT YET APPROVED"` to exclude timelines entirely. The system prompt's grounding rules were rewritten so the AI defers timeline questions to a discovery call instead of citing unapproved estimates, and never cites case-study figures as guarantees.
- `DEPLOYMENT-HOSTINGER.md` now has a "Knowledge Approval Status" section listing exactly what's approved vs. not, and what the owner needs to sign off on before timelines can be used.

✅ **5. Security fix: EmailNotifier header injection**
- `Reply-To:` header previously used `$lead['email']` directly (user-controlled, unsanitized) — a header injection vector (CRLF injection to add arbitrary headers like `Bcc:`).
- Added `sanitize_email()`: validates with `FILTER_VALIDATE_EMAIL` and strips `\r`, `\n`, `\0` defensively. Invalid emails cause the function to return `false` without sending, rather than sending with a malformed header.
- Removed the unsupported "we'll get back to you within 24 hours" promise from the lead confirmation email (replaced with "will respond soon" — no specific commitment).
- Added `strip_tags()` on all user-supplied fields (name, company, problem) in the email body.

✅ **6. Tests expanded (static/structural only — see "Honest Limitation" below)**
- `tests/test-executable.php` expanded with explicit checks for: conversation persistence ordering, role rejection, session ID consistency, multi-turn history merge logic, lead-independent-of-email behavior, input validation (oversized/invalid-type/malformed-JSON), and email header injection prevention.
- `DEPLOYMENT-HOSTINGER.md` now has a full "Manual Test Plan" section with exact steps, expected results, and an explicit list of what requires a live Hostinger/PHP/MySQL/OpenAI environment to actually verify.

### ⚠️ Honest Limitation: No PHP Runtime Available in This Dev Environment

This sandbox has no `php` binary, and Docker Desktop's daemon is not running (so a PHP container couldn't be started either). This means:
- **`php -l` syntax checks were NOT executed** by me — I manually read every changed file line-by-line to check for syntax errors, plus a basic brace/paren balance script, but this is not a substitute for the real PHP parser.
- **No test in `tests/*.php` has actually been run.** They are written to be run with `php tests/test-executable.php` and are correct as far as careful manual/static review can confirm, but "written and manually reviewed" is not the same claim as "executed and passed" — I will not repeat the earlier mistake of reporting unverified work as complete.
- **You (or a CI/Hostinger environment with PHP installed) must run `php -l` on every file and execute the test suite before trusting this.**

## Completed Fixes (Nov 24, 2026 - Round 1: Core Pass)

✅ **Priority 1: Conversation Context - FIXED**
- Created ConversationManager.php to store/retrieve messages per session
- Updated chat.php to call get_history() before each OpenAI request
- OpenAIClient now has chat_with_history() that includes prior messages in payload
- Messages saved to conversation_messages table after each exchange
- Bounded by max 10 messages per context window

✅ **Priority 2: Lead Notification - FIXED**
- Integrated EmailNotifier into capture-lead.php
- Calls notify_lead() + confirm_lead() after successful DB save
- Email failures don't fail the lead capture (graceful degradation)
- Leads are saved even if notification fails

✅ **Priority 3: Knowledge Loading & Branding - FIXED**
- Removed "powered by Claude AI" from system prompt
- KnowledgeLoader now loads both facts.json and system-instructions.md
- Both files incorporated into build_system_prompt()

✅ **Priority 4: API Security Hardening - FIXED**
- chat.php: 5KB request size limit enforced before JSON decode
- capture-lead.php: 10KB request size limit
- Type checking on all required fields
- Field length validation (name ≤200, email ≤255, company ≤500, problem ≤2000)
- Safe JSON decode with error handling
- Widget.js: replaced innerHTML with textContent for error display (XSS prevention)

## User Review Findings (Nov 24, 2026)

Earlier findings that required fixes:

### 1. Conversation Context - ✅ FIXED
**Was:** Only latest message sent to OpenAI
**Now:** ConversationManager retrieves & includes prior messages (last 10, bounded context)

### 2. Lead Notification - ✅ FIXED  
**Was:** capture-lead.php saved leads but didn't notify
**Now:** EmailNotifier called after save; doesn't fail lead capture if email fails

### 3. Knowledge Loading - ✅ FIXED
**Was:** Only facts.json loaded; "powered by Claude AI" branding in prompt
**Now:** Both facts.json and system-instructions.md loaded; branding removed

### 4. API Security - ✅ FIXED
**Was:** Basic validation only
**Now:** Request size limits (5KB chat, 10KB leads), field type/length validation, safe JSON decode

### 5. Frontend - ✅ FIXED
**Was:** widget.js used innerHTML for errors (XSS risk)
**Now:** Uses textContent; builds HTML structure safely

### 6. Deployment Guide - ⏳ PENDING
**Status:** Not yet addressed
**Remaining work:**
- Document exact directory structure for Hostinger
- Path mappings (backend, frontend, config locations)
- Widget installation instructions
- Database path
- Config/secrets.php location

### 7. Tests - ⏳ PENDING
**Status:** Not yet addressed  
**Remaining work:**
- Implement actual test suite
- Test conversation history (multi-turn support)
- Test lead capture & notification
- Test API validation & rate limiting
- Test error handling
- Document test execution results

## Current Status (precise, not a percentage estimate)

**Implemented and committed, verified by manual source review:**
- Conversation history persistence (FK-safe), latest-10-chronological retrieval, role restriction
- Lead capture independent of email success
- Knowledge approval filtering (timelines excluded, pricing gated on approval)
- Config path consistency between code and deployment docs
- Email header injection prevention
- Request size limits and field validation

**NOT yet done — do not assume these pass:**
- No automated test in this repo has been executed by any tool in this session (no PHP runtime available here)
- No live database has been used to verify the FK fix actually prevents the constraint violation it's designed to prevent
- No live OpenAI call has verified multi-turn history actually produces context-aware replies
- No live Hostinger environment has been used

## Remaining Work

1. **Run `php -l` on every file** and **execute `tests/test-executable.php`** on a machine with PHP — see "Honest Limitation" above
2. **Run the Manual Test Plan** in `DEPLOYMENT-HOSTINGER.md` section "Manual Test Plan" against a real PHP+MySQL+OpenAI setup
3. **Owner approval** of timelines/case-study figures in `knowledge/facts.json` (see `owner_confirmations_needed`) before those are enabled
4. **Optional enhancements** - Origin header validation beyond CORS, voice (Phase 4, currently disabled)

## Implementation Notes

**ConversationManager:**
- Stores session_id, role (user/assistant), content
- Retrieves last 10 messages per session (bounded context)
- Integrates transparently into chat.php request flow

**EmailNotifier Integration:**
- Called after lead save (not in transaction, so failures don't rollback)
- Error-logged but doesn't fail the API response
- Lead data confirmed saved to DB before attempting email

**Knowledge Loading:**
- KnowledgeLoader loads facts.json at startup
- Appends system-instructions.md content to system prompt
- Prevents any vendor branding in generated prompt

**API Security:**
- Request body size checked BEFORE JSON decode (prevents memory exhaustion)
- Field lengths validated per business requirements
- Type checking on all inputs
- Safe exception handling with no stack traces to client

**Frontend:**
- widget.js uses textContent for all user-visible error/success messages
- No innerHTML injection of server responses
- HTML structure created via appendChild (safe DOM manipulation)

## Lesson From This Review Cycle

An earlier pass in this repo reported commits as complete when they had not actually been pushed to GitHub, and separately reported "all tests passed" based on static code presence rather than execution. Both were corrected after independent review. Going forward in this file: "fixed" means the code change is committed and manually verified line-by-line; it does NOT mean "executed and passed" unless a real test run is cited with its actual output.
