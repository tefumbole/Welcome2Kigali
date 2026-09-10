# Welcome 2 Kigali Expats Club

Live site: [welcome2kigali.net](https://welcome2kigali.net)

This repository is **not** BeyondTechWorld. Pushes here go to `github.com/tefumbole/Welcome2Kigali.git` only.

| | Welcome 2 Kigali | BeyondTechWorld |
|---|---|---|
| GitHub | `Welcome2Kigali` | `BeyondTechWorld` |
| App path | `/var/www/welcome2kigali-app/laravel-app` | `/var/www/beyondtechworld` |
| Database | `welcome2kigali` | `beyondtechworld_laravel` |

## Deploy (this site only)

```bash
W2K_SKIP_VERSION_BUMP=1 W2K_SSH_HOST=myvps \
  W2K_REMOTE=/var/www/welcome2kigali-app/laravel-app \
  bash tools/deploy-w2k-preview-files.sh
```

Do not run `tools/deploy-beyondtechworld-*.sh` from this tree.
