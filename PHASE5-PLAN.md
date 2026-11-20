# Phase 5: Lead workflow & launch

**Goal:** connect lead capture, test end-to-end, deploy to production.

## Checklist

- [ ] Backend: lead capture → email notification
- [ ] Frontend: lead form + consent checkbox
- [ ] Retention: 12 months leads, 90 days transcripts
- [ ] Tests: pricing, grounding, mobile, accessibility, cost
- [ ] Staging: deploy and test all 65 scenarios
- [ ] Kill switch: disable widget globally if needed
- [ ] Monitoring: set up spending alerts
- [ ] Go-live: gradual rollout with rollback plan

## Files to create

1. `backend/lib/EmailNotifier.php` — send lead emails
2. `ai-consultant/widget-lead.js` — lead capture form
3. `PHASE5-TESTS.md` — final 65 test scenarios
4. `DEPLOYMENT.md` — staging → production guide

## Key decisions needed

1. Lead email to: hello@mrakibulislam.com ✓
2. Booking URL: cal.com link ✓
3. Retention: 12 mo leads, 90 day transcripts ✓
4. Voice: beta, off by default ✓
5. Kill switch location: in config/secrets.php

## Timeline

- Backend notifications: 1–2 hrs
- Lead form UI: 1–2 hrs
- Testing: 2–3 hrs
- **Total: 4–7 hrs**

## Go-live steps

1. Test on staging (all 65 scenarios pass)
2. Enable feature flag in production config
3. Monitor: spending, errors, leads received
4. Gradual rollout (10% traffic first)
5. Full launch or rollback based on metrics

## Success criteria

- All leads captured with consent
- Emails received in real-time
- No errors in production
- Mobile works
- Spending under budget
- Ready for live traffic
