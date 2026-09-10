# Welcome 2 Kigali Expats Club

Laravel app for **https://welcome2kigali.net**.

This tree is not BeyondTechWorld. Do not push to `BeyondTechWorld.git` or deploy into `/var/www/beyondtechworld`.

| | Welcome 2 Kigali | BeyondTechWorld |
|---|---|---|
| GitHub | `tefumbole/Welcome2Kigali` | `tefumbole/BeyondTechWorld` |
| Production path | `/var/www/welcome2kigali-app/laravel-app` | `/var/www/beyondtechworld` |
| Database | `welcome2kigali` | `beyondtechworld_laravel` |

## Deploy

```bash
W2K_SKIP_VERSION_BUMP=1 W2K_SSH_HOST=myvps \
  W2K_REMOTE=/var/www/welcome2kigali-app/laravel-app \
  bash tools/deploy-w2k-preview-files.sh
```

## Local

```bash
cd laravel-app
cp .env.example .env
# DB_DATABASE=welcome2kigali  (never beyondtechworld_laravel)
php artisan migrate
```
