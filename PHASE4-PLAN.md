# Phase 4: Voice mode beta — Implementation plan

**Goal:** add low-latency voice chat using OpenAI's Realtime API. Beta, off by default.

**Scope:**
- Browser voice input (microphone permission, recording indicator)
- Realtime API integration (WebRTC, low-latency audio)
- Short-lived credentials issued by backend
- Context continuity between text and voice
- Error handling (permission denied, network loss, timeout)
- Cost controls (session limits, spending alerts)
- Voice beta toggle in settings

## Architecture

### New backend endpoint

**POST `/ai-consultant/api/voice-token.php`**

Returns a short-lived credential for the Realtime API.

Request:
```json
{
  "session_id": "sess_...",
  "consent_to_record": true
}
```

Response (success):
```json
{
  "success": true,
  "token": "gsk_...",  // OpenAI session token
  "expires_in": 3600,  // 1 hour
  "model": "gpt-4o-realtime-preview"
}
```

Response (error):
```json
{
  "success": false,
  "error": "Voice not available. Try text chat instead."
}
```

Validation:
- Consent flag must be true (affirmative opt-in)
- Rate limit: 1 token per minute per session (prevents abuse)
- Max 10 concurrent sessions per server
- Max 30 minutes per session (cost control)

### Widget changes

**New UI elements:**
- Microphone button (next to send button when voice is available)
- Recording indicator (pulsing circle, "● Listening...")
- Transcript display (real-time, as user speaks)
- Voice permission dialog (browser-native, first use)
- Status: "Connecting...", "Recording...", "Processing...", "Error"

**Voice vs text:**
- Text and voice share the same session
- Switching voice→text or text→voice keeps conversation context
- Transcript of voice is shown as a user message, response as text
- No audio stored by default (config: optional)

## Implementation details

### Phase 4A: Backend voice token endpoint

File: `backend/api/voice-token.php` (~100 lines)

```php
// Load config and middleware
// Check consent flag
// Rate limit per session (1 per minute)
// Check concurrent session limit
// Issue short-lived token from OpenAI
// Log to audit_logs
// Return token or error
```

Security:
- Token is short-lived (1 hour)
- Only issued with explicit consent
- One token per request (no token reuse)
- Audit log every token issued
- Spending alerts if approaching budget limit

### Phase 4B: Widget voice UI

New files:
- `ai-consultant/widget-voice.js` (~500 lines)
  - Realtime API client (WebRTC, audio handling)
  - Microphone permission request
  - Audio input/output management
  - Error recovery and fallback

- `ai-consultant/widget-voice.css` (~100 lines)
  - Microphone button styling
  - Recording indicator animation
  - Transcript display
  - Permission dialog

Changes to `widget.js`:
- Add microphone button conditionally (if voice available)
- Load voice module only on demand (lazy load)
- Share session context between text and voice
- Switch input mode (text ↔ voice)

### Voice flow

1. **User clicks microphone button**
   - Browser requests microphone permission (native dialog)
   - If denied: show "Permission denied" message, suggest text instead

2. **Permission granted**
   - Widget calls `/ai-consultant/api/voice-token.php`
   - Backend returns short-lived Realtime API token

3. **Connect to Realtime API**
   - Open WebRTC connection to OpenAI
   - Start recording user audio
   - Show "● Recording..." indicator

4. **User speaks**
   - Audio streams to Realtime API in real-time
   - Assistant responds via voice or text (configurable)
   - Transcript shown as it's recognized

5. **User stops speaking**
   - Widget detects silence (VAD - voice activity detection)
   - Sends audio packet, closes stream
   - Shows "Processing..." while waiting

6. **Response**
   - Assistant's reply as audio and/or text
   - User can interrupt by speaking again
   - Conversation context stays intact

7. **End session**
   - User clicks stop button or timeout occurs
   - Close WebRTC connection
   - Clean up: no audio stored (unless configured)

## Cost controls

**OpenAI Realtime pricing:** ~$0.10 per minute of input + output

Safeguards:
- Max 30 minutes per session (hard limit: ~$3 cost)
- Warn user at 20 minutes ("5 minutes remaining")
- Budget alert in backend config (e.g., $100/day)
- Kill switch: disable voice globally if budget is exceeded
- Audit log every voice session with start/end time

## Configuration

Add to `config/example-secrets.php`:

```php
'voice' => [
    'enabled' => false,  // Off by default (beta)
    'max_session_minutes' => 30,
    'max_concurrent_sessions' => 10,
    'budget_per_day_cents' => 10000,  // $100/day
    'warn_at_minutes' => 20,
],
```

## Browser support

- Chrome/Edge 90+
- Firefox 115+ (later release; Realtime is newer)
- Safari 16+ (later; WebRTC support)
- Mobile: iOS Safari 16+, Android Chrome 90+

Fallback if Realtime API unavailable:
- Show text-only chat (no error, just no microphone button)

## Testing scenarios

| # | Test | Expected | Status |
|---|------|----------|--------|
| 1 | Page loads, voice disabled | No microphone button | PENDING |
| 2 | Enable voice in config | Microphone button appears | PENDING |
| 3 | Click microphone button | Browser asks for permission | PENDING |
| 4 | Deny permission | "Permission denied" message, button disabled | PENDING |
| 5 | Grant permission | "● Connecting..." → "● Recording..." | PENDING |
| 6 | Speak a message | Transcript appears in real-time | PENDING |
| 7 | Stop speaking | "Processing..." shown, response appears | PENDING |
| 8 | Interrupt response | Click stop button, reconnect to microphone | PENDING |
| 9 | Switch text→voice | Same session, context preserved | PENDING |
| 10 | Switch voice→text | Same session, transcript shown as message | PENDING |
| 11 | 30-minute session | "Time's up" message, voice ends | PENDING |
| 12 | No audio (silent) | Timeout after 30 sec, reconnect | PENDING |
| 13 | Network drops | Graceful error, suggest retry | PENDING |
| 14 | Mobile permission | iOS/Android native permission dialog | PENDING |
| 15 | Mobile recording | Microphone works on iPhone and Android | PENDING |

## Exit criteria

- Voice toggle works (enabled/disabled)
- Permission handling correct (allow/deny)
- Realtime API connects and streams audio
- Transcript appears in real-time
- Voice-to-text context preserved
- Text-to-voice context preserved
- Cost limits enforced
- 15 tests pass
- Ready for staging

## Files to create/modify

**New:**
- `backend/api/voice-token.php` (~100 lines)
- `ai-consultant/widget-voice.js` (~500 lines)
- `ai-consultant/widget-voice.css` (~100 lines)
- `ai-consultant/tests/voice-tests.md` (~50 lines)
- `PHASE4-PLAN.md` (this file)

**Modified:**
- `widget.js` — add microphone button, load voice module
- `widget.css` — space for microphone button
- `config/example-secrets.php` — voice config section
- `backend/lib/AuditLog.php` — log voice sessions (cost tracking)

## Timeline estimate

- Backend token endpoint: 2–3 hours
- Voice UI and Realtime integration: 4–6 hours
- Testing and debugging: 2–3 hours
- **Total Phase 4: 8–12 hours** (longer than text, more complexity)

## Known risks

1. **Realtime API latency:** may vary by region/load
2. **Browser audio permissions:** vary by browser and user settings
3. **Mobile audio:** input/output handling differs by OS
4. **Cost overruns:** need to monitor budget alerts
5. **VAD (voice activity detection):** OpenAI's built-in; may have false positives

## Fallback strategy

If Realtime API is unavailable or config disables voice:
- No microphone button appears
- Widget works in text-only mode
- No error shown (graceful degradation)
- User can still use text chat
