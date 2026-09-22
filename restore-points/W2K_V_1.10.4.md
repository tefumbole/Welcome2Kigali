# Restore Point: W2K_V_1.10.4

**Date:** 2026-09-22  
**Git tag:** `W2K_V_1.10.4`  
**Login version label:** `1.10.4`  
**Commit:** see `git rev-list -n1 W2K_V_1.10.4`

## Snapshot includes

- Public Menu, Membership (including Demo), and About restore / self-heal
- About: Vision and Mission, then Our Leadership, then identity cards in the original grid
- Identity cards fly in slowly, one after another, then stay visible together
- Customer delete is blocked while sales, quotations, registrations, or memberships still point at that customer
- Sale delete no longer 500s when the linked customer row is already missing
- Admin dashboard shows a dash instead of crashing if a quotation or sale customer is missing
- Sidebar names in English, French, and Kinyarwanda

## Restore code to this point

```bash
git fetch --tags
git checkout W2K_V_1.10.4
# or on a branch:
git checkout -b restore-w2k-v1.10.4 W2K_V_1.10.4
```

## Redeploy production

```bash
W2K_SKIP_VERSION_BUMP=1 W2K_SSH_HOST=myvps W2K_REMOTE=/var/www/welcome2kigali-app/laravel-app bash tools/deploy-w2k-preview-files.sh
```

After a restore, return `main` to latest intentionally — do not leave production on a detached tag unless planned.

## Database / file backup

Production backup created with this restore point:

| File | Location |
|------|----------|
| SQL dump (~408 KB) | `backups/production-W2K_V_1.10.4-20260922-171111.sql` (also on VPS) |
| Uploads/images tar (~2.4 MB) | `backups/production-files-W2K_V_1.10.4-20260922-171111.tar.gz` |
| Manifest | `backups/production-W2K_V_1.10.4-20260922-171111.manifest.txt` |

VPS copies: `/var/www/welcome2kigali-app/backups/production-W2K_V_1.10.4-20260922-171111.*`

Database name: `welcome2kigali`
