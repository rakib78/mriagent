# Phase 3 Widget Tests — Text chat MVP

Manual tests to run after integrating the widget into `mri/index.html`.

## Test environment

- Browser: Chrome, Firefox, Safari (desktop and mobile)
- Domain: https://mrakibulislam.com (staging or production)
- Backend: `/ai-consultant/api/` endpoints must be live and working

## Test scenarios

### Launch and UI

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 1 | Page loads | Launcher appears bottom-right | | Gold circle, chat icon |
| 2 | Click launcher | Chat window slides in, focus on input | | No horizontal scroll |
| 3 | Type message | Characters appear in input field | | Textarea resizes up to 80px |
| 4 | Press Escape | Chat closes smoothly | | Launcher still visible |
| 5 | Click X button | Chat closes | | Same as Escape |
| 6 | Reload page | Launcher reappears, chat closed | | Session reset (intentional) |
| 7 | Mobile viewport (375px) | Chat full-width, launcher centered | | No horizontal scroll |
| 8 | Tablet viewport (768px) | Chat 400px, launcher right | | Responsive breakpoint works |

### Conversation & grounding

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 9 | "What services do you offer?" | Lists: Shopify, Zendesk, HubSpot, AI | | From facts.json |
| 10 | "How much is a Zendesk implementation?" | Cites: "Full CRM Build, USD 4,500-12,000" | | Exact price from facts.json |
| 11 | "Can you give me a quote?" | "I'd need to understand your scope first. Tell me about..." | | Doesn't invent prices |
| 12 | "Promise me two weeks" | "I can't promise a timeline without knowing scope" | | Qualified, honest |
| 13 | "What's not in your services?" | Declines, says "not on the site" | | Stays grounded |
| 14 | Ask for a discount | "No discounts available. Email hello@..." | | Doesn't negotiate |
| 15 | "Take me to your booking page" | Shares Cal.com link: `https://cal.com/md.-rakibul-islam-vjxvrf/30min` | | Exact URL from config |

### Error handling

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 16 | Empty message + send | Input field stays open, no send | | Form validation |
| 17 | Very long message (>1000 chars) | "Message is too long" alert or rejection | | Validation on widget |
| 18 | Send 11 messages in 60 sec | 11th gets "Please wait..." (rate limit) | | 10 per minute limit |
| 19 | Offline / network down | "Service unavailable. Please email..." | | Graceful fallback |
| 20 | API returns error | "Service temporarily unavailable" (no stack trace) | | Error hidden, contact info shown |
| 21 | API timeout (>15 sec) | Message retry encouraged | | Reasonable timeout |

### Session & state

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 22 | Send 3 messages, close, reopen | Messages gone (session reset) | | Intentional; phase 2 feature |
| 23 | Same session ID across messages | All messages in same conversation have same `session_id` | | Verifiable in API calls |
| 24 | Scroll history | Old messages stay visible, new messages at bottom | | Scrollable message area |
| 25 | Multiple tabs | Each tab has separate session | | Tab isolation (session storage) |

### Accessibility

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 26 | Tab key | Focus visible on launcher, input, send button | | Keyboard navigation works |
| 27 | Keyboard only | Open (space on launcher), type, Enter to send, Escape to close | | No mouse required |
| 28 | Screen reader | Buttons and labels are announced (aria-label) | | ARIA attributes present |
| 29 | High contrast | Text readable on both light and dark backgrounds | | CSS supports both modes |

### Performance & security

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 30 | No XSS via message | HTML tags or script in message appear as text, not executed | | Sanitised on display |
| 31 | No API key in browser | Dev Tools Network tab shows no hardcoded keys | | Server-side only |
| 32 | Load time | Widget script <50ms to initialize | | Fast startup |
| 33 | Bundle size | widget.js ~8-10 KB (gzipped), widget.css ~2-3 KB | | Small payload |

### Mobile-specific

| # | Test | Expected | Pass | Notes |
|---|------|----------|------|-------|
| 34 | Mobile Safari (iOS) | Widget appears and works on iPhone | | iOS 11+ support |
| 35 | Android Chrome | Widget appears and works on Android | | Android 6+ support |
| 36 | Soft keyboard | Keyboard doesn't cover send button | | Input area stays accessible |
| 37 | Portrait/landscape | Reorients and stays usable | | Responsive layout |
| 38 | Launcher not covering nav | Launcher doesn't block mobile nav, footer button | | Positioning check |

## Pricing accuracy tests

**These verify facts.json matches mri/pricing.html:**

| # | Service | Expected price | Actual | Pass |
|---|---------|-----------------|--------|------|
| 39 | CRM Audit & Fix | $1,200–$2,500 | | |
| 40 | Full CRM Build | $4,500–$12,000 | | |
| 41 | CRM Monthly Retainer | $2,800–$4,000 | | |
| 42 | Shopify Audit & CRO Fix | $1,500–$3,500 | | |
| 43 | Custom Shopify Build | $4,000–$15,000 | | |
| 44 | Shopify Dev Retainer | $2,000–$4,000 | | |
| 45 | Upwork hourly | $45–$75 | | |

## Sign-off

- [ ] All UI tests pass (1–8)
- [ ] All conversation tests pass (9–15)
- [ ] All error handling tests pass (16–21)
- [ ] All session tests pass (22–25)
- [ ] Accessibility tests pass (26–29)
- [ ] Performance / security tests pass (30–33)
- [ ] Mobile tests pass (34–38)
- [ ] Pricing accuracy tests pass (39–45)

**Ready for Phase 4 (voice):** When all tests above pass, the widget is ready for staging deployment.

**Next phase:** Add voice mode (Realtime API) with beta off by default.
