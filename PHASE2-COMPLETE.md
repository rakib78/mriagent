# Phase 2: Secure backend foundation — COMPLETE

**Date:** 2026-11-20 · **Status:** Code written, not deployed, not tested on Hostinger yet.

## Deliverables

All files are in `mriagent/backend/`.

### Core files created

1. **Config & secrets** (never tracked in git)
   - `config/example-secrets.php` — template showing structure
   - `.gitignore` — excludes `config/secrets.php`

2. **Library classes** (reusable, testable)
   - `lib/OpenAIClient.php` — calls OpenAI API, hides errors
   - `lib/RateLimiter.php` — session + IP-based rate limiting
   - `lib/LeadStore.php` — MySQL lead capture with soft deletes
   - `lib/AuditLog.php` — logging without exposing secrets

3. **API endpoints** (Phase 2)
   - `api/chat.php` — POST, text messages, rate-limited
   - `api/capture-lead.php` — POST, lead capture with consent & honeypot
   - `api/health.php` — GET, service status check

4. **Database**
   - `migrations/001-schema.sql` — tables: leads, conversations, audit_logs

5. **Documentation**
   - `README.md` — setup, endpoints, security checklist
   - `tests/security-tests.md` — 20+ test scenarios

## Security features implemented

✓ Secrets stored outside web root (config/secrets.php)  
✓ Rate limiting per IP + session (dual-layer)  
✓ CORS locked to mrakibulislam.com only  
✓ Input validation (length, format, type)  
✓ Prepared statements (no SQL injection)  
✓ Error responses never expose keys, paths, or database errors  
✓ Honeypot on lead capture  
✓ Email hashing in audit logs  
✓ Soft deletes with retention policy (12 months leads, 90 days conversations)  

## Deployment blockers

⚠️ **SMTP password in `mri/crm-discovery/crm-discovery-submit.php` is NOT rotated**  
- Code is ready to deploy, but new functionality must wait until this is fixed.
- Rotating it is a 2-minute Hostinger task; moving it to config/secrets.php is included in Phase 2.

## Files not yet created (waiting for Phase 3)

- `ai-consultant/widget.js` — browser chat launcher and UI
- `ai-consultant/widget.css` — responsive styles
- System instructions integrated into the assistant (now in `knowledge/system-instructions.md`)

## Next steps

### Before Phase 3 (Phase 2 sign-off)

1. **Rotate SMTP password** in Hostinger and move to `config/secrets.php`.
2. **Create MySQL database** on Hostinger and run migrations.
3. **Create `config/secrets.php`** on the server with real values.
4. **Test locally** (the security tests in `tests/security-tests.md`).
5. **Verify health check** endpoint works: `GET /ai-consultant/api/health.php` returns OK.

### Phase 3 (text chat MVP)

- Build the widget (`widget.js`, `widget.css`).
- Connect it to `/ai-consultant/api/chat.php`.
- Test pricing accuracy, grounding, and lead capture flow.
- Deploy to staging and verify end-to-end.

## Code quality

- No external dependencies (no Composer, no npm).
- Uses only PHP stdlib: PDO (MySQL), stream functions (HTTP), sessions.
- Hostinger shared hosting compatible.
- Error handling: logs problems, returns safe responses to clients.
- Audit trail: every action logged to MySQL without exposing PII.

## Files not committed

Nothing is committed yet. When you're ready:
```bash
cd mriagent
git add backend/ knowledge/ PHASE2-PLAN.md PHASE2-COMPLETE.md AUDIT.md
git commit -m "Phase 1-2: knowledge base and secure backend foundation"
git push
```

The `config/secrets.php` will never be tracked.
