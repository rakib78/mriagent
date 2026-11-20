# Deployment Guide

## Pre-deployment checklist

- [ ] All 65 tests pass on staging
- [ ] config/secrets.php created on Hostinger
- [ ] MySQL database migrated
- [ ] SMTP password rotated (Phase 2 blocker resolved)
- [ ] Voice enabled/disabled in config
- [ ] Daily spending budget set
- [ ] Kill switch location documented
- [ ] Rollback plan written

## Staging deployment

```bash
cd /path/to/mri
git pull origin main

# If new files:
cp -r ai-consultant/ public_html/
cp backend/api/*.php public_html/ai-consultant/api/

# Test
curl https://staging.mrakibulislam.com/ai-consultant/widget.js
curl https://staging.mrakibulislam.com/ai-consultant/api/health.php
```

## Production deployment (gradual)

### Step 1: Deploy code (10% traffic)

```bash
git push origin main  # Push to GitHub
# Or manual SFTP to Hostinger public_html/

# Add to index.html (before </body>):
<link rel="stylesheet" href="/ai-consultant/widget.css">
<script defer src="/ai-consultant/widget.js"></script>
```

### Step 2: Monitor (1 hour)

- Watch errors in logs
- Check health endpoint
- Verify leads being captured
- Monitor spending

### Step 3: Full rollout or rollback

If good: enable for 100%
If bad: remove script tags, restore from backup

## Kill switch

**To disable widget globally:**

```php
// config/secrets.php
'widget' => [
    'enabled' => false,  // Disables entire widget
]
```

Then reload browser.

## Rollback

```bash
git revert <commit-hash>
git push origin main
# Or restore from backup
```

## Monitoring

- **Daily:** check spending (OpenAI dashboard)
- **Daily:** verify leads in email + database
- **Weekly:** review test failures
- **Monthly:** audit logs

## Contacts

- OpenAI support: platform.openai.com/account/billing
- Hostinger support: hpanel.hostinger.com
- Rakibul: hello@mrakibulislam.com
