# Phase 4 Voice Tests — Realtime API integration

Manual and automated tests for voice mode.

## Setup

Voice beta: disabled by default. Enable for testing:

```php
// config/secrets.php
'voice' => [
    'enabled' => true,  // Enable for Phase 4 testing
    'max_session_minutes' => 30,
    'max_concurrent_sessions' => 10,
    'budget_per_day_cents' => 10000,  // $100/day
    'warn_at_minutes' => 20,
    'model' => 'gpt-4o-realtime-preview',
]
```

## Test scenarios

### Voice availability

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 1 | Voice disabled in config | No microphone button | | Graceful fallback to text |
| 2 | Voice enabled in config | Microphone button appears | | Next to send button |
| 3 | Unsupported browser | No microphone button (no error) | | Fallback to text |
| 4 | HTTPS required | Microphone works (or error if HTTP) | | Security requirement |

### Permission handling

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 5 | First click on mic button | Browser asks for permission | | Native permission dialog |
| 6 | Grant permission | "● Recording..." appears | | Status displayed |
| 7 | Deny permission | "Microphone permission denied" message | | Suggests text instead |
| 8 | Revoke permission in OS | Subsequent mic clicks show error | | Graceful handling |

### Voice session

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 9 | Start recording | Microphone button pulses red | | Visual feedback |
| 10 | Speak a message | Transcript appears in real-time (Phase 4B) | | As speech is recognized |
| 11 | Stop speaking | "Processing..." shown briefly | | VAD detects silence |
| 12 | Response arrives | Reply shown as text or audio (config) | | Same session, context kept |
| 13 | Interrupt response | Mic stops playback, re-enables recording | | User can speak again |
| 14 | Click stop button | Voice session ends cleanly | | No errors |

### Session limits

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 15 | 20 minutes in session | "5 minutes remaining" warning | | At warnAtMinutes |
| 16 | 30 minutes in session | "Session time limit reached" + stop | | Hard limit |
| 17 | Consecutive sessions | 1 token per minute allowed | | Rate limit enforced |
| 18 | 10 concurrent sessions | 11th gets "at capacity" error | | Server load protection |

### Context continuity

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 19 | Switch text → voice | Same session ID, context preserved | | Shared conversation |
| 20 | Speak in voice mode | Message and response stay in chat | | Visible as text |
| 21 | Transcript of voice | Shown as "user" message in chat | | Treated like text input |
| 22 | Resume text after voice | Chat continues, same context | | Seamless switching |

### Error recovery

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 23 | Network drops mid-session | Error shown, reconnect offered | | Graceful fallback |
| 24 | Realtime API timeout | "Service unavailable" message | | No crash, can retry |
| 25 | No audio (silent >30s) | Auto-timeout, suggest retry | | VAD timeout |
| 26 | Audio input error | "Microphone error" message | | Browser/OS issue |
| 27 | Microphone already in use | Error message, suggest closing other apps | | Resource contention |

### Browser support

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 28 | Chrome 90+ | Voice works | | Full support |
| 29 | Firefox 115+ | Voice works | | Later release, Realtime new |
| 30 | Safari 16+ | Voice works | | WebRTC support |
| 31 | Edge 90+ | Voice works (Chromium-based) | | Same as Chrome |
| 32 | Safari < 16 | No mic button, text only | | Graceful fallback |
| 33 | iOS Safari 16+ | Voice works on iPhone | | Mobile support |
| 34 | Android Chrome 90+ | Voice works on Android | | Mobile support |

### Mobile specific

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 35 | iOS permission | Native permission dialog | | First use |
| 36 | Android permission | Native permission dialog | | First use |
| 37 | Mobile input | Mic button accessible, no overlap | | UX on mobile |
| 38 | Mobile to desktop | Same session, switch devices | | Session preserved (intentional: tab-local) |
| 39 | Soft keyboard + voice | Keyboard doesn't block voice UI | | Layout shift handling |

### Cost controls

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 40 | Budget config set | Backend enforces spending limit | | $100/day example |
| 41 | Daily budget reached | New voice sessions rejected | | Kill switch active |
| 42 | Audit log tracks cost | Every session logged with start/end | | For billing |
| 43 | 30-min session cost | ~$3 charged (estimation) | | Transparent to user |

### Accessibility

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 44 | Keyboard: Tab to mic | Focus visible | | Keyboard nav |
| 45 | Keyboard: Space/Enter | Toggles mic on/off | | Activate button |
| 46 | Screen reader | "Start voice chat" announced | | Aria-label present |
| 47 | High contrast | Mic button readable | | Text and color |

### Performance

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 48 | Load widget with voice | No delay (<100ms extra) | | Lazy-loaded |
| 49 | Token request latency | <1 sec to get token | | Fast backend |
| 50 | Real-time latency | <500ms for user audio → response | | Low-latency API |

## Sign-off criteria

- [ ] Tests 1–50 pass (or skipped for unsupported browsers)
- [ ] Voice disabled by default (security, cost)
- [ ] No audio stored unless explicitly enabled
- [ ] Cost controls enforced
- [ ] Fallback to text if voice unavailable
- [ ] Ready for staging deployment

## Notes

- Phase 4A (backend token endpoint): tests 1–4, 15–18, 40–43
- Phase 4B (voice UI): tests 5–14, 19–39, 44–50
- Phase 4B requires OpenAI Realtime API access and test budget
