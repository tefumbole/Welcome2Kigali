#!/usr/bin/env bash
# Copy preview Laravel app + DB onto welcome2kigali.net (replace Coming Soon).
# Never writes into /var/www/beyondtechworld.
set -euo pipefail

PREVIEW="${W2K_PREVIEW_ROOT:-/var/www/welcome2kigali-preview/laravel-app}"
DEST="${W2K_PROD_ROOT:-/var/www/welcome2kigali-app/laravel-app}"
WEB_USER="${WEB_USER:-www-data}"
NGINX_SRC="$(cd "$(dirname "$0")" && pwd)/nginx/welcome2kigali.conf"
AVAILABLE="/etc/nginx/sites-available/welcome2kigali"
ENABLED="/etc/nginx/sites-enabled/welcome2kigali"

case "$DEST" in
  /var/www/beyondtechworld|/var/www/beyondtechworld/*)
    echo "REFUSING: will not write into Beyond."
    exit 1
    ;;
esac

if [[ ! -d "$PREVIEW" ]]; then
  echo "Preview app missing at $PREVIEW"
  exit 1
fi

if [[ ! -f /etc/letsencrypt/live/welcome2kigali.net/fullchain.pem ]]; then
  echo "SSL cert for welcome2kigali.net is missing."
  exit 1
fi

echo "==> Copy preview files to $DEST"
mkdir -p "$(dirname "$DEST")"
rsync -a --delete \
  --exclude 'storage/logs/*' \
  --exclude 'storage/framework/cache/data/*' \
  --exclude 'storage/framework/views/*' \
  --exclude 'storage/framework/sessions/*' \
  "$PREVIEW/" "$DEST/"

mkdir -p \
  "$DEST/storage/framework/cache/data" \
  "$DEST/storage/framework/sessions" \
  "$DEST/storage/framework/views" \
  "$DEST/storage/logs" \
  "$DEST/bootstrap/cache" \
  "$DEST/public/uploads/membership"

echo "==> Production database (clone of preview)"
mysql -e "
CREATE DATABASE IF NOT EXISTS welcome2kigali CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON welcome2kigali.* TO 'w2k_preview'@'localhost';
GRANT ALL PRIVILEGES ON welcome2kigali.* TO 'w2k_preview'@'127.0.0.1';
FLUSH PRIVILEGES;
"
mysqldump --single-transaction --quick welcome2kigali_preview | mysql welcome2kigali

echo "==> Production .env"
if [[ ! -f "$DEST/.env" ]]; then
  echo "Copied app is missing .env"
  exit 1
fi
python3 - <<'PY'
from pathlib import Path
p = Path("/var/www/welcome2kigali-app/laravel-app/.env")
text = p.read_text()
repl = {
    "APP_ENV": "production",
    "APP_DEBUG": "false",
    "APP_URL": "https://welcome2kigali.net",
    "DB_DATABASE": "welcome2kigali",
    "BEYOND_SKIP_OTP": "true",
}
out = []
seen = set()
for line in text.splitlines():
    if not line or line.lstrip().startswith("#") or "=" not in line:
        out.append(line)
        continue
    key = line.split("=", 1)[0].strip()
    if key in repl:
        out.append(f"{key}={repl[key]}")
        seen.add(key)
    else:
        out.append(line)
for key, val in repl.items():
    if key not in seen:
        out.append(f"{key}={val}")
p.write_text("\n".join(out) + "\n")
PY

echo "==> Permissions"
chown -R "$WEB_USER:$WEB_USER" /var/www/welcome2kigali-app
find /var/www/welcome2kigali-app -type d -exec chmod 755 {} \;
find "$DEST" -type f -exec chmod 644 {} \;
chmod 755 "$DEST/artisan"
chown -R "$WEB_USER:$WEB_USER" "$DEST/storage" "$DEST/bootstrap/cache" "$DEST/public/uploads"
chmod -R ug+rwx "$DEST/storage" "$DEST/bootstrap/cache"

echo "==> Artisan as $WEB_USER"
sudo -u "$WEB_USER" php "$DEST/artisan" config:clear
sudo -u "$WEB_USER" php "$DEST/artisan" cache:clear
sudo -u "$WEB_USER" php "$DEST/artisan" view:clear
sudo -u "$WEB_USER" php "$DEST/artisan" route:clear

echo "==> Create admin Nasrah Umwali"
sudo -u "$WEB_USER" php -r "
require '$DEST/vendor/autoload.php';
\$app = require '$DEST/bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
\$roleId = Illuminate\Support\Facades\DB::table('roles')->where('name', 'Admin')->value('id') ?: 4;
\$phone = '+250 782 024 793';
\$email = 'nasrah.umwali@welcome2kigali.net';
\$name = 'Nasrah Umwali';
\$password = Illuminate\Support\Facades\Hash::make('system');
\$payload = [
    'name' => \$name,
    'email' => \$email,
    'password' => \$password,
    'phone' => \$phone,
    'role_id' => \$roleId,
    'is_active' => 1,
    'is_deleted' => 0,
    'otp_verify' => 1,
    'must_set_password' => 0,
];
\$user = App\User::whereRaw('LOWER(name) = ?', [strtolower(\$name)])
    ->orWhereRaw('LOWER(email) = ?', [strtolower(\$email)])
    ->first();
if (\$user) { \$user->fill(\$payload)->save(); } else { \$user = App\User::create(\$payload); }
if (Illuminate\Support\Facades\Schema::hasTable('be_users')) {
    \$beyond = App\BeyondUser::whereRaw('LOWER(username) = ?', ['nasrah.umwali'])
        ->orWhereRaw('LOWER(email) = ?', [strtolower(\$email)])
        ->first();
    \$bp = [
        'name' => \$name,
        'email' => \$email,
        'username' => 'nasrah.umwali',
        'password_hash' => \$password,
        'role' => 'super_admin',
        'status' => 'active',
        'phone' => \$phone,
        'must_change_credentials' => false,
    ];
    if (\$beyond) { \$beyond->fill(\$bp)->save(); }
    else { \$bp['id'] = (string) Illuminate\Support\Str::uuid(); \$beyond = App\BeyondUser::create(\$bp); }
    if (Illuminate\Support\Facades\Schema::hasTable('be_profiles')) {
        App\BeyondProfile::updateOrCreate(
            ['id' => \$beyond->id],
            ['email' => \$email, 'full_name' => \$name, 'phone' => \$phone, 'role' => 'super_admin', 'username' => 'nasrah.umwali', 'status' => 'active']
        );
    }
}
echo 'Admin ready id='.\$user->id.PHP_EOL;
"

echo "==> Nginx for welcome2kigali.net"
install -m 644 "$NGINX_SRC" "$AVAILABLE"
ln -sfn "$AVAILABLE" "$ENABLED"
nginx -t
systemctl reload nginx

echo "Live: https://welcome2kigali.net"
echo "Login: Nasrah Umwali / system"
echo "Phone: +250 782 024 793"
