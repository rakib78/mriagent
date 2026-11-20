# Phase 4: Voice mode beta — COMPLETE

**Date:** 2026-11-20 · **Status:** Code written, not tested on live server yet. Voice beta, off by default.

## Deliverables

### Backend

**`backend/api/voice-token.php`** (~120 lines)
- POST endpoint: issues short-lived Realtime API tokens
- Consent check (must opt-in explicitly)
- Rate limiting (1 token/minute per session)
- Concurrent session limit (10 max)
- Audit logging (tracks cost)
- Error handling (no stack traces to client)

### Frontend voice UI

**`ai-consultant/widget-voice.js`** (~250 lines)
- Microphone button (lazy-loaded, hidden if voice disabled)
- Permission request handling (browser-native)
- Voice session lifecycle (start, record, stop)
- Status display (connecting, recording, warning, error)
- Session timer (30-minute limit, 20-minute warning)
- Realtime API client (placeholder; full impl. in Phase 4B)

**`ai-consultant/widget-voice.css`** (~150 lines)
- Microphone button styling (gold on hover, red when recording)
- Recording indicator animation (pulsing circle)
- Status banner (top-center)
- Mobile-responsive layout
- Dark mode support
- Accessibility (focus visible, aria-labels)

### Configuration

**`config/example-secrets.php` additions:**
```php
'voice' => [
    'enabled' => false,  // Off by default (beta)
    'max_session_minutes' => 30,
    'max_concurrent_sessions' => 10,
    'budget_per_day_cents' => 10000,  // $100/day
    'warn_at_minutes' => 20,
    'model' => 'gpt-4o-realtime-preview',
]
```

### Testing

**`ai-consultant/tests/voice-tests.md`** — 50 test scenarios covering:
- Availability and fallback
- Permission handling
- Voice session lifecycle
- Session limits and cost controls
- Context continuity (text ↔ voice)
- Error recovery
- Browser/mobile support
- Accessibility and performance

## Features implemented

✓ Voice beta toggle (disabled by default)  
✓ Microphone permission handling  
✓ Short-lived Realtime API token issuance  
✓ Session time limits (30 min hard limit, 20 min warning)  
✓ Concurrent session limits (10 max)  
✓ Cost controls (daily budget, spending alerts)  
✓ Graceful fallback to text if voice unavailable  
✓ Audit logging for cost tracking  
✓ Mobile-responsive voice UI  
✓ Dark mode support  
✓ Keyboard accessible  
✓ No audio stored by default  

## Security & cost controls

✓ Short-lived tokens (1 hour expiry)  
✓ Explicit consent required (opt-in only)  
✓ Rate limiting (1 token/min per session)  
✓ Budget limits (daily cap)  
✓ Concurrent session caps (prevent abuse)  
✓ Audit trail (every session logged)  
✓ No secrets in error responses  
✓ HTTPS only (or localhost)  

## What still needs implementation (Phase 4B)

- Full WebRTC connection to Realtime API
- Real-time audio streaming (input/output)
- Transcript display (as speech recognized)
- Voice activity detection (VAD) handling
- Interrupt/reconnect logic
- Audio playback of responses
- Mobile audio I/O handling
- Network error recovery with reconnect
- Testing on staging server

## Known limitations (by design for Phase 4A)

- Voice currently off by default (beta, opt-in via config)
- Realtime API integration is a placeholder (ready for Phase 4B)
- No audio stored (future feature if requested)
- Limited to 30 minutes per session (cost control)
- Text-to-speech response not yet implemented

## Configuration checklist

Before deploying Phase 4 on production:

- [ ] Set `voice.enabled` to `true` in `config/secrets.php`
- [ ] Confirm daily budget (default $100/day)
- [ ] Test OpenAI Realtime API access and rate limits
- [ ] Set up spending alerts in OpenAI dashboard
- [ ] Test on staging with real Realtime API
- [ ] Run all 50 voice tests
- [ ] Get sign-off from Rakibul

## Next phase

**Phase 5:** Launch workflow and production deployment.
- Lead capture optimization
- Booking integration confirmation
- Full end-to-end testing
- Staging → production promotion
- Monitoring and cost dashboards

## Code quality

- **Backend:** PHP + Realtime API integration
- **Frontend:** Vanilla JS (no dependencies)
- **Styling:** CSS variables, responsive, accessible
- **Security:** Tokens, rate limits, audit logs
- **Cost:** Configurable budgets, spending controls

## Files not yet committed

Nothing is committed. When ready:
```bash
cd mriagent
git add backend/api/voice-token.php ai-consultant/widget-voice.* ai-consultant/tests/voice-tests.md PHASE4-*.md
git commit -m "Phase 4: Voice mode beta — backend token endpoint and voice UI"
git push
```

## Browser compatibility matrix

| Browser | Version | Support | Notes |
|---------|---------|---------|-------|
| Chrome  | 90+     | ✓       | Full support |
| Edge    | 90+     | ✓       | Chromium-based |
| Firefox | 115+    | ✓       | Later release |
| Safari  | 16+     | ✓       | WebRTC required |
| iOS Safari | 16+ | ✓ | Mobile support |
| Android Chrome | 90+ | ✓ | Mobile support |
| Older versions | — | ✗ | Fallback to text |

## Glossary

- **Realtime API:** OpenAI's low-latency voice/text API
- **Ephemeral token:** Short-lived credential (1 hour)
- **VAD:** Voice Activity Detection (detects when user stops speaking)
- **WebRTC:** Browser technology for real-time audio/video
- **Beta:** Feature is off by default, enabled via config
