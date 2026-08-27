#!/usr/bin/env bash
# Deploy the FULL Welcome 2 Kigali / Beyond clone to a preview URL.
# Leaves Coming Soon on welcome2kigali.net and never writes into Beyond.
set -euo pipefail

DEST="${W2K_PREVIEW_ROOT:-/var/www/welcome2kigali-preview}"
APP="$DEST/laravel-app"
WEB_USER="${WEB_USER:-www-data}"
AVAILABLE="/etc/nginx/sites-available/welcome2kigali-preview"
ENABLED="/etc/nginx/sites-enabled/welcome2kigali-preview"
SRC_NGINX="$(cd "$(dirname "$0")" && pwd)/nginx/welcome2kigali-preview.conf"

case "$DEST" in
  /var/www/beyondtechworld|/var/www/beyondtechworld/*|/var/www/welcome2kigali)
    echo "REFUSING: $DEST would overwrite Coming Soon or Beyond."
    exit 1
    ;;
esac

if [[ ! -d "$APP" ]]; then
  echo "Laravel app not found at $APP — rsync the project first."
  exit 1
fi

# Composer vendor is installed on the VPS. Frontend assets live in public/vendor
# (Bootstrap, jQuery, icons). Never rsync with a blanket --exclude 'vendor/'
# or the admin UI will load without CSS/JS (modals render as page content).
if [[ ! -f "$APP/public/vendor/bootstrap/css/bootstrap.min.css" ]]; then
  echo "WARNING: public/vendor assets are missing. Sync laravel-app/public/vendor before using /admin."
fi

echo "==> Isolated preview database (not Beyond)"
mysql -e "
CREATE DATABASE IF NOT EXISTS welcome2kigali_preview CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'w2k_preview'@'localhost' IDENTIFIED BY 'w2k_preview';
CREATE USER IF NOT EXISTS 'w2k_preview'@'127.0.0.1' IDENTIFIED BY 'w2k_preview';
GRANT ALL PRIVILEGES ON welcome2kigali_preview.* TO 'w2k_preview'@'localhost';
GRANT ALL PRIVILEGES ON welcome2kigali_preview.* TO 'w2k_preview'@'127.0.0.1';
FLUSH PRIVILEGES;
"

cd "$APP"
if [[ ! -f .env ]]; then
  echo "==> Writing isolated .env"
  cat > .env <<'ENV'
APP_NAME="Welcome 2 Kigali Expats Club"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://187.124.2.238:8090
BEYOND_SKIP_OTP=true
LOG_CHANNEL=stack
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=welcome2kigali_preview
DB_USERNAME=w2k_preview
DB_PASSWORD=w2k_preview
BROADCAST_DRIVER=log
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
MAIL_DRIVER=log
WHATSAPP_SERVICE=WASENDER
WHATSAPP_DEFAULT_COUNTRY_CODE=250
COMPANY_NAME="Welcome 2 Kigali Expats Club"
ENV
  php artisan key:generate --force
fi

chown -R "$WEB_USER:$WEB_USER" "$DEST"
find "$DEST" -type d -exec chmod 755 {} \;
find "$APP" -type f -exec chmod 644 {} \;
chmod 755 "$APP/artisan"
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
chown -R "$WEB_USER:$WEB_USER" storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

echo "==> Migrate + cafe menu (preview DB only)"
run_artisan() {
  sudo -u "$WEB_USER" php "$APP/artisan" "$@"
}
if [ -f "$APP/VERSION" ]; then
  echo "==> Sync version $(tr -d '[:space:]' < "$APP/VERSION") to settings"
  run_artisan tinker --execute="echo App\\Support\\AppVersion::syncToSettings();" 2>/dev/null || true
fi
run_artisan migrate --force
run_artisan db:seed --class=CafeMenuSeeder --force || true
run_artisan db:seed --class=LocalAdminSeeder --force || true

echo "==> Nginx preview vhost on :8090 (Coming Soon stays on welcome2kigali.net)"
install -m 644 "$SRC_NGINX" "$AVAILABLE"
ln -sf "$AVAILABLE" "$ENABLED"
nginx -t
systemctl reload nginx
# Do not reload php-fpm unless needed — Beyond uses the same 7.4 pool.

chown -R "$WEB_USER:$WEB_USER" storage bootstrap/cache

echo ""
echo "Preview (full clone):  http://preview.187.124.2.238.sslip.io"
echo "Optional pretty DNS:   A preview.welcome2kigali.net → 187.124.2.238"
echo "Coming Soon unchanged: https://welcome2kigali.net"
echo "Beyond unchanged:      https://beyondtechworld.com"
echo "Login: admin@welcome2kigali.local / ChangeMe@123456"
