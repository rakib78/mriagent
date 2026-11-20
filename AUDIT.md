# mri repository — read-only technical audit

Date: 2026-11-20 · Repo audited: `rakib78/mri` (3 commits, branch `main`) · No files in `mri` were modified.
Companion to the project brief for the mrakibulislam.com AI technical consultant.

## 1. Blocker: exposed SMTP password

- `crm-discovery/crm-discovery-submit.php` line 20 holds the SMTP password for `forms@mrakibulislam.com` as a plain constant in a public repo. (Value intentionally not reproduced here.)
- It appears in one commit only: `7cf9832` ("Add files via upload", 2026-11-20). Earlier commits do not contain it, so history cleanup is practical.
- Required order:
  1. Rotate the mailbox password in Hostinger. Treat the old one as compromised.
  2. Remove the credential from tracked source; load it from config/env outside the web root.
  3. Decide whether to rewrite history (needs a force-push; owner approval required).
- A scan of all history found no other secrets (only a placeholder in an old README).

## 2. Repo layout

- Static HTML plus PHP handlers: `index.html` (~80 KB, inline CSS/JS), `pricing.html`, `portfolio.html`, `llms.txt`, `robots.txt`, `sitemap.xml`, `blog/` (6 posts + `old/`), `crm-discovery/` (form + PHPMailer), `images/`.
- Cleanup candidates: `blog/files (2).zip` (duplicates the six posts), `default.php` and `ecgig/default.php` (Hostinger placeholders), `done_generate-thumbnails.php` (one-off script that makes outbound requests when visited; its own comments say to delete it).
- No `.gitignore`, no build step, and no evidence of how the repo is deployed.

## 3. PHP handler weaknesses

`contact.php`
- 60-second rate limit is session-based; a client that drops cookies bypasses it.
- Uses `mail()` (deliverability/blocking risk).
- `FILTER_SANITIZE_EMAIL` used in header fields; review for header injection and use strict validation.
- Input is HTML-escaped before going into a plain-text email (mangles `&` etc.).
- CORS locked to the site domain (good). Honeypot present.

`crm-discovery-submit.php`
- No rate limit, no CSRF/origin check, no request-size cap.
- Auto-reply goes to any address found in submitted text, so it could be abused to email third parties.
- Output escaping is done properly.

## 4. Widget insertion point

- Contact section `#contact` at `index.html` line 892; footer line 962; main script from line 1000.
- Safest: two lines before `</body>` — deferred `/ai-consultant/widget.js` and a `widget.css` link.
- Nav is fixed at z-index 500, mobile menu 490: keep the launcher below both on mobile and clear of the footer "Book a call" button.
- Existing contact form fetch logic is self-contained and unaffected.

## 5. Knowledge sources

- `pricing.html`: seven USD ranges, matching the brief; duplicated in JSON-LD, so two copies must stay in sync.
- `llms.txt`: good short fact sheet, but names the current employer; confirm that is fine for an AI to repeat.
- Case studies: `portfolio.html` and the six blog posts. FAQ: homepage FAQ section.
- Keep exact facts (services, prices, billing units, contact, booking URL) as structured data with source URL and review date; use retrieval only for explanatory content.

## 6. Backend recommendation (provisional)

- Start with Hostinger PHP: one place, already in use. A PHP endpoint calls the OpenAI API; secrets live outside the web root.
- Consider Supabase only if Hostinger cannot keep secrets out of the web root, make outbound HTTPS calls, or provide MySQL.
- Cannot be finalised until the Hostinger questions below are answered.

## 7. Plan and tests

- Follow phases 0–5 and the 16 test scenarios in the brief.
- Add: cookie-less rate-limit bypass test, auto-reply abuse test, and a pricing-accuracy check against `pricing.html`.

## 8. Questions for Rakibul

1. Has the SMTP password been rotated?
2. Rewrite history, or just rotate and remove?
3. Hostinger plan, PHP version, MySQL availability, secret storage options?
4. How is the repo deployed to Hostinger?
5. Which booking URL should the assistant use?
6. Are the prices approved for AI use? Is naming the employer in `llms.txt` acceptable?
7. Homepage only at first? Is voice a beta?
8. Retention period for conversations and leads?
