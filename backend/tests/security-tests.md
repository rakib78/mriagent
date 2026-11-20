# Phase 2 Security Tests

These tests verify that the backend is safe before Phase 3 (widget deployment).

## Test suite

### Input validation

| Test | Input | Expected | Status |
|------|-------|----------|--------|
| Valid message | `{"message": "What services?"}` | 200, success true | PENDING |
| Missing message | `{}` | 422, error message |  PENDING |
| Empty message | `{"message": ""}` | 422, error message | PENDING |
| Long message (1001 chars) | `{"message": "x" * 1001}` | 422, error message | PENDING |
| Null message | `{"message": null}` | 422, error message | PENDING |
| HTML injection | `{"message": "<script>..."}` | 200, escaped or rejected | PENDING |

### Rate limiting

| Test | Setup | Expected | Status |
|------|-------|----------|--------|
| 10 messages in 60 sec | Loop 10 times, sleep 2 sec | All succeed | PENDING |
| 11th message in 60 sec | Continue from above | 429, rate limit error | PENDING |
| Dropped session | Send 10, drop cookies, send 1 more | 429 (IP limit still active) | PENDING |

### CORS

| Test | Origin | Expected | Status |
|------|--------|----------|--------|
| Correct origin | `mrakibulislam.com` | 200 | PENDING |
| Wrong origin | `example.com` | 403 or failure | PENDING |
| No origin | No `Origin` header | 200 (browser request) | PENDING |

### Lead capture

| Test | Input | Expected | Status |
|------|-------|----------|--------|
| Valid lead | `name, email, company, problem, consent: true` | 200, success true | PENDING |
| No consent | Same but `consent: false` | 422, error | PENDING |
| Invalid email | `email: "invalid"` | 422, error | PENDING |
| Honeypot filled | `website_hp: "spam"` | 200, but not saved | PENDING |
| Rate limit | 2 leads in 60 sec | 2nd gets 429 | PENDING |

### Error handling

| Test | Scenario | Expected | Status |
|------|----------|----------|--------|
| OpenAI down | Mock unreachable API | 503, no key/endpoint exposed | PENDING |
| MySQL down | Mock unreachable DB | 500, no credentials exposed | PENDING |
| Config missing | Move `secrets.php` away | 500, generic error | PENDING |
| Oversized payload | POST 10 MB | 413 or 422 | PENDING |

### SQL injection

| Test | Input | Expected | Status |
|------|-------|----------|--------|
| SQL in name | `name: "'; DROP TABLE..."` | Escaped, saved as literal string | PENDING |
| SQL in email | `email: "' OR 1=1"` | Escaped or rejected | PENDING |

### Secrets in logs/responses

| Test | Scenario | Expected | Status |
|------|----------|----------|--------|
| Error response | Any 5xx error | No API key, database password, stack trace | PENDING |
| Audit log | Check database | No plaintext email, only hashed | PENDING |
| Server error | Trigger a PHP error | Error not returned to client | PENDING |

## Checklist before Phase 3

- [ ] All input validation tests pass
- [ ] Rate limits active on both endpoints
- [ ] CORS allows `mrakibulislam.com` only
- [ ] Lead capture requires consent
- [ ] No secrets in error responses
- [ ] No secrets in git (config/secrets.php is in .gitignore)
- [ ] Database schema created on test server
- [ ] Health check endpoint returns OK
- [ ] Honeypot works (honeypot requests don't save)
