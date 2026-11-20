# AI Consultant Widget — Integration guide

Add the widget to `mri/index.html` with two lines before `</body>`.

## Quick start

Open `mri/index.html` and find the closing `</body>` tag near the end (around line 1113).

Add these lines just before it:

```html
<!-- AI Consultant Widget -->
<link rel="stylesheet" href="/ai-consultant/widget.css">
<script defer src="/ai-consultant/widget.js"></script>
```

**Complete example:**

```html
  </main>

  <!-- ══════ FOOTER ══════ -->
  <footer>
    ...
  </footer>

  <!-- Scripts -->
  <script>
    // existing site scripts
  </script>

  <!-- AI Consultant Widget -->
  <link rel="stylesheet" href="/ai-consultant/widget.css">
  <script defer src="/ai-consultant/widget.js"></script>

</body>
</html>
```

## How it works

1. **CSS loads first:** styles are applied; widget launcher is hidden off-screen until JS runs.
2. **Script loads with `defer`:** waits until DOM is ready, then self-initializes.
3. **Launcher appears:** gold circle with chat icon, bottom-right on desktop, bottom-center on mobile.
4. **User clicks launcher:** chat window slides in with welcome message.
5. **User types and sends:** message goes to `/ai-consultant/api/chat.php`, response appears.

## What the widget does

- **Creates a launcher button:** gold circle, bottom-right corner (mobile: center)
- **Opens a chat window:** 400px wide on desktop, full-width on mobile (90vw)
- **Sends messages to the backend:** calls `/ai-consultant/api/chat.php`
- **Displays responses:** with typing indicator while waiting
- **Remembers session:** within the same browser tab (resets on reload)
- **Shows errors gracefully:** if service is down, users can email or call

## What it does NOT do (yet)

- Voice (Phase 4)
- Lead capture (manual implementation; not in this phase)
- Storage of conversations (stays in memory, lost on reload)
- Analytics or tracking

## Customization

Edit variables in `widget.css`:

```css
:root {
  --mri-primary: #C9A84C;        /* Launcher and button color */
  --mri-primary-dark: #A87E2C;   /* Hover color */
  --mri-user-bg: #3b82f6;        /* User message background */
  --mri-assistant-bg: #f0f0f0;   /* Assistant message background */
}
```

Responsive breakpoints:
- **Mobile:** 600px and below (full-width chat, centered launcher)
- **Desktop:** 601px and above (400px chat, right-aligned launcher)

Dark mode: automatically applies if user's system prefers dark mode (`prefers-color-scheme: dark`).

## Browser support

- Chrome/Edge 60+
- Firefox 55+
- Safari 11+
- Mobile: iOS Safari 11+, Android Chrome 60+

Requires JavaScript enabled. No external libraries or polyfills needed.

## Testing checklist

After adding the widget:

- [ ] Launcher appears in bottom-right corner (desktop)
- [ ] Launcher appears in bottom-center (mobile viewport)
- [ ] Click launcher: chat window slides in
- [ ] Welcome message is visible
- [ ] Type a message and press Enter: message appears and sends
- [ ] Wait for response: typing indicator shows, then reply appears
- [ ] Press Escape: chat closes
- [ ] Click X button: chat closes
- [ ] Reload page: session reset, messages gone (intentional)
- [ ] Mobile: chat is full-width with margin
- [ ] No horizontal scroll on any device
- [ ] Dark mode works (system preference)

## Troubleshooting

**Widget doesn't appear**
- Check browser console for errors (F12)
- Verify file paths: `/ai-consultant/widget.js` and `/ai-consultant/widget.css`
- Ensure JavaScript is enabled
- Verify HTTPS (or localhost)

**Messages don't send**
- Check Network tab in DevTools (F12)
- Verify `/ai-consultant/api/chat.php` is responding (200 status)
- Check API response for errors
- Verify `config/secrets.php` exists on the server and OpenAI key is valid

**Launcher is hidden**
- Check z-index conflicts with other fixed elements
- Verify CSS is loading (check Network tab)
- Look for console errors about CSS parsing

**Chat appears behind other content**
- Check other elements' z-index values
- Widget is z-index: 400 (should be above most content)
- Adjust if needed in `widget.css`

## Support

If the widget is broken:
1. Check browser console (F12 > Console tab)
2. Check Network requests (F12 > Network tab)
3. Email hello@mrakibulislam.com with a screenshot and console error
