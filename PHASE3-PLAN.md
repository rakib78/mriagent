# Phase 3: Text chat MVP — Implementation plan

**Goal:** build a responsive browser widget that connects to the Phase 2 backend and delivers the text chat experience.

**Scope:**
- Launcher button (discreet, doesn't cover content on mobile)
- Chat window (messages, input, loading states, errors)
- Session management (remembers context within a tab)
- Integration: 2 lines before `</body>` on `mri/index.html`
- Voice beta: off by default (Phase 4)
- Testing: pricing accuracy, grounding, scope qualification

## Architecture

```
ai-consultant/
├── widget.js          # Main script (lazy-loaded, ~8 KB gzipped target)
├── widget.css         # Styles (responsive, ~3 KB target)
├── INTEGRATION.md     # Copy-paste guide for index.html
└── tests/
    └── chat-tests.md  # Test scenarios
```

### Entry point (in mri/index.html before </body>)

```html
<!-- AI Consultant Widget -->
<link rel="stylesheet" href="/ai-consultant/widget.css">
<script defer src="/ai-consultant/widget.js"></script>
```

The script self-initializes: creates the launcher, listens for clicks, and builds the chat UI on-demand.

## Widget behavior

### Launcher
- Fixed position, bottom-right corner (desktop) or bottom-center (mobile)
- Icon: circle with "?" or speech bubble
- Label: "Chat with our consultant" (tooltip on hover)
- Color: brand gold (#C9A84C on the site) or CTA blue
- Never covers: footer "Book a call" button, mobile navigation, text
- Z-index: 400 (below nav 500, above main content)

### Chat window
- Modal dialog, centered on desktop, full-width on mobile (with margin)
- Header: "AI Consultant" title, close button (X)
- Body: message list (scrollable)
- Input: text field + send button (or Enter to send)
- Footer: "Powered by Claude" or similar (optional)
- Animations: smooth slide-in/out, fade messages
- Keyboard: Tab to focus, Escape to close, Enter to send

### Message rendering
- **User messages:** right-aligned, blue or brand color, timestamp optional
- **Assistant messages:** left-aligned, gray background, smooth typing effect or instant display
- **Loading:** spinner or pulsing dots while waiting for API
- **Errors:** red text, retry button if appropriate

### State management
- Session ID: generated once per browser session, sent with each message
- Message history: kept in memory (not persisted; reloads if page reloads)
- Consent: separate opt-in checkbox before capturing a lead
- Errors: shown inline, don't dismiss the widget

## Endpoints used

- `POST /ai-consultant/api/chat.php` — send message, get reply
- `GET /ai-consultant/api/health.php` — check service status on load (optional)

## Security & performance

- **HTTPS only:** check and warn if not on https://
- **No analytics:** don't track user behavior
- **No cookies:** use session storage only (stays within tab)
- **Local storage:** optional, only for "last read" or UI state (never personal data)
- **CSP-friendly:** no inline event handlers, no eval()
- **Keyboard accessible:** focus visible, aria-labels, semantic HTML
- **Performance:** defer script, lazy-load CSS, no render-blocking

## Styling approach

- CSS variables for colors, spacing, z-index (easy to customize)
- Mobile-first: start at 375px width
- No external fonts or libraries
- Dark/light mode support (prefers-color-scheme)
- Fallback fonts: system stack

## Testing scenarios

| Scenario | Expected | Status |
|----------|----------|--------|
| Load page | Launcher appears, hidden chat window | PENDING |
| Click launcher | Chat window fades in, focus on input | PENDING |
| Type message | Message appears as user sends | PENDING |
| Send message | API call, spinner shown, reply appears | PENDING |
| Long reply | Message wraps, scrolls if needed | PENDING |
| API error | Error message, suggestion to email or call | PENDING |
| Rate limit | "Please wait..." message, disable send | PENDING |
| Network down | Graceful fallback to email/contact | PENDING |
| Close button | Window fades out, can re-open | PENDING |
| Escape key | Window closes | PENDING |
| Mobile viewport | Full-width, stacked layout, centered | PENDING |
| Session preserved | Reload page, same session ID, old messages gone | PENDING |
| Responsive text | No horizontal scroll at any breakpoint | PENDING |

## Exit criteria

- Widget loads without errors
- Chat sends and receives messages from API
- Pricing accuracy verified (test scenario #2 from brief)
- Lead capture shown when appropriate
- Mobile and desktop both work
- No secrets or keys in browser
- Ready for staging deployment

## Files to create

1. `widget.js` — ~400 lines
2. `widget.css` — ~200 lines
3. `INTEGRATION.md` — setup instructions
4. `tests/chat-tests.md` — test scenarios
