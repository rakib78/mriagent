# Critical Findings & Required Fixes

## User Review Findings (Nov 24, 2026)

The implementation was overstated. Following findings require fixes before staging deployment:

### 1. Conversation Context - BROKEN
**Current state:** Only latest message sent to OpenAI
**Impact:** MVP cannot remember prior context in same session
**Fix required:**
- Create conversation_messages table (schema updated)
- Implement ConversationManager to store/retrieve messages
- Send last 5-10 messages to OpenAI with each request
- Bounded by token limit (max 2000 tokens for context)

### 2. Lead Notification - BROKEN
**Current state:** capture-lead.php saves lead but doesn't email
**Impact:** Leads captured but Rakibul not notified
**Fix required:**
- Call EmailNotifier.php after successful lead save
- Don't fail if email fails (lead still saved)
- Return success if lead saved (even if email fails)
- Log email failures

### 3. Knowledge Loading - INCOMPLETE
**Current state:** Loads facts.json but references "Claude AI" in prompt
**Issue:** Incorrect branding in system prompt
**Fix required:**
- Remove "powered by Claude AI" reference
- Load knowledge/system-instructions.md as well
- Verify both files actually used

### 4. API Security - INCOMPLETE
**Current state:** Basic validation only
**Missing:**
- Request body size limit (before JSON decode)
- Field length validation
- Type checking (not just presence)
- Safe exception handling
- Proper Origin header check

### 5. Frontend - INCOMPLETE
**Current state:** widget.js uses innerHTML for errors
**Risk:** XSS vulnerability in error messages
**Fix required:**
- Replace innerHTML with textContent for user messages
- Verify no voice controls present
- Check widget respects enabled/disabled config

### 6. Deployment Guide - VAGUE
**Current state:** Uses ambiguous `cp -r` commands
**Problem:** Unclear where files actually go on Hostinger
**Fix required:**
- Document exact source → destination paths
- Show resulting directory structure
- Specify config/secrets.php location
- Exact widget URL paths

### 7. Tests - NOT RUN
**Current state:** Report claims 65 tests
**Reality:** 0 tests executed
**Fix required:**
- Actually run tests
- Report pass/fail results
- Document which tests require Hostinger

### 8. System Prompt - FIXED IN PREVIOUS COMMIT
✓ KnowledgeLoader created
? But need to verify it's actually called on every request

## Status

**Code quality:** 40% (foundation present, critical features incomplete)
**MVP readiness:** 30% (conversation history missing, lead notification broken)
**Deployable:** 10% (too many gaps for production use)

## Work Required Before Staging

1. Implement conversation history (HIGH PRIORITY)
2. Fix lead notification integration (HIGH PRIORITY)
3. Hardening: request size limits, type validation (MEDIUM)
4. Frontend: textContent fixes (MEDIUM)
5. Actual test execution (MEDIUM)
6. Accurate deployment documentation (LOW)

## Honest Assessment

The groundwork is solid (database, API structure, widget UI) but the MVP lacks two critical features:
- **No context memory** (user cannot have a conversation)
- **Lead notification broken** (leads captured but not emailed)

These must be fixed before any deployment.
