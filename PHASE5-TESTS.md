# Phase 5 Final Tests — 65 scenarios

## Core functionality

1. Widget loads and works
2. Text chat sends/receives
3. Voice beta (if enabled)
4. Lead capture saves
5. Emails received
6. Pricing accurate
7. Session preserved
8. Mobile responsive

## Grounding (brief check)

9. Services list correct
10. Prices match facts.json
11. Booking URL works
12. No invented info

## Mobile

13. iOS Safari
14. Android Chrome
15. Portrait/landscape
16. No horizontal scroll

## Accessibility

17. Keyboard nav (Tab, Enter, Escape)
18. Screen reader
19. High contrast
20. Focus visible

## Errors

21. Network down
22. API error
23. Rate limit
24. Permission denied (voice)

## Security

25. No XSS
26. No keys in browser
27. HTTPS only
28. CORS correct

## Lead capture

29. Form appears
30. Consent required
31. Email validated
32. Honeypot works
33. Lead saved to DB
34. Email to Rakibul
35. Confirmation to lead
36. Retention policy (12 mo)

## Cost controls

37. Voice budget enforced
38. Daily limit
39. Audit log complete
40. Spending alerts

## Context (text ↔ voice)

41. Same session ID
42. Message history kept
43. Switch modes smooth
44. Transcript shown

## End-to-end

45. User lands on site
46. Clicks widget
47. Asks about Shopify
48. Gets response
49. Asks pricing
50. Gets exact price
51. Asks for quote
52. Told needs discovery
53. Wants to book
54. Click booking link → Cal.com
55. Want to email instead
56. Click contact → form or email
57. Submit lead
58. Get confirmation email
59. Rakibul gets notified
60. Lead in database

## Deployment checks

61. No secrets in git
62. config/secrets.php created on server
63. Database migrated
64. All endpoints responding
65. Health check passes

## Sign-off

- [ ] 60+ tests pass
- [ ] No critical errors
- [ ] Mobile works
- [ ] Emails working
- [ ] Ready for production
