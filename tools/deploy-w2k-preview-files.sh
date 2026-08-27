#!/usr/bin/env bash
# Sync changed W2K Laravel files to the preview VPS and bump version each run.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
APP="$ROOT/laravel-app"
SSH_HOST="${W2K_SSH_HOST:-alphabridge-ts}"
REMOTE="/var/www/welcome2kigali-preview/laravel-app"

if [ "${W2K_SKIP_VERSION_BUMP:-0}" != "1" ]; then
  bash "$ROOT/tools/bump-w2k-version.sh"
fi

mkdir -p "$APP/public/uploads/membership"

cd "$APP"

FILES=(
  VERSION
  app/Support/SiteBrand.php
  app/Support/WhatsAppMessage.php
  app/Support/SiteMenu.php
  app/Support/SiteContent.php
  app/Support/MembershipQr.php
  app/Support/CafeOrder.php
  app/Support/StaffAccess.php
  app/Support/AppVersion.php
  app/CustomerGroup.php
  app/Console/Kernel.php
  app/Console/Commands/ProcessMemberships.php
  app/Services/MembershipService.php
  app/Services/MembershipNotifier.php
  app/Http/Controllers/MembershipAdminController.php
  app/Http/Controllers/MembershipPublicController.php
  app/Http/Controllers/MembershipPosController.php
  app/Http/Controllers/SaleController.php
  app/Http/Controllers/ProductController.php
  app/Http/Controllers/CustomerGroupController.php
  app/Http/Controllers/CartController.php
  app/Http/Controllers/HomeController.php
  app/Http/Controllers/LanguageController.php
  app/Http/Controllers/BeyondAuthController.php
  app/Http/Controllers/BeyondController.php
  app/Http/Middleware/EncryptCookies.php
  app/Providers/AppServiceProvider.php
  app/Support/SiteI18n.php
  resources/lang/en/menu.php
  resources/lang/fr/menu.php
  resources/views/beyond/cart.blade.php
  resources/views/beyond/register-now.blade.php
  resources/views/beyond/auth/layout.blade.php
  resources/views/beyond/auth/login.blade.php
  database/migrations/2026_08_27_150000_create_membership_module.php
  routes/web.php
  resources/lang/en/site.php
  resources/lang/fr/site.php
  resources/views/pdf/membership_confirmation.blade.php
  resources/views/layout/main.blade.php
  resources/views/beyond/layout.blade.php
  resources/views/beyond/home.blade.php
  resources/views/beyond/about.blade.php
  resources/views/beyond/contact.blade.php
  resources/views/beyond/menu.blade.php
  resources/views/beyond/checkout.blade.php
  resources/views/beyond/partials/contact_section.blade.php
  resources/views/beyond/partials/office_location.blade.php
  resources/views/sale/pos.blade.php
  resources/views/sale/create.blade.php
  resources/views/sale/edit.blade.php
  resources/views/sale/create_sale.blade.php
  resources/views/booking/pos.blade.php
  resources/views/booking/create.blade.php
  resources/views/booking/edit.blade.php
  resources/views/booking/create_sale.blade.php
  resources/views/product/create.blade.php
  resources/views/product/edit.blade.php
  public/uploads/membership/.gitkeep
  public/branding/footer-swoosh.svg
  public/branding/footer-wave.svg
  public/branding/w2k-landing.png
)

for f in app/Membership*.php; do
  [ -f "$f" ] && FILES+=("$f")
done

EXISTING=()
for f in "${FILES[@]}"; do
  if [ -f "$f" ]; then
    EXISTING+=("$f")
  else
    echo "WARN: missing $f" >&2
  fi
done

/usr/bin/rsync -az --relative "${EXISTING[@]}" "$SSH_HOST:$REMOTE/"
/usr/bin/rsync -az --relative \
  resources/views/membership \
  resources/views/beyond/membership \
  "$SSH_HOST:$REMOTE/"

if [ -f "$APP/app/Support/SiteI18n.php" ]; then
  /usr/bin/rsync -az --relative app/Support/SiteI18n.php "$SSH_HOST:$REMOTE/"
fi

/usr/bin/ssh -o BatchMode=yes -o ConnectTimeout=25 "$SSH_HOST" 'bash -s' <<EOS
set -e
APP=$REMOTE
mkdir -p "\$APP/public/uploads/membership" \
  "\$APP/resources/views/membership" \
  "\$APP/resources/views/beyond/membership"
chown -R www-data:www-data "\$APP/public/uploads/membership" "\$APP/storage" "\$APP/bootstrap/cache"
chmod -R ug+rwx "\$APP/public/uploads/membership" "\$APP/storage" "\$APP/bootstrap/cache"
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_08_27_150000_create_membership_module.php
sudo -u www-data php "\$APP/artisan" view:clear
sudo -u www-data php "\$APP/artisan" cache:clear
sudo -u www-data php -r "require '\$APP/vendor/autoload.php'; \\\$app = require '\$APP/bootstrap/app.php'; \\\$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap(); echo App\\\\Support\\\\AppVersion::syncToSettings().PHP_EOL;"
chown -R www-data:www-data "\$APP/storage" "\$APP/bootstrap/cache"
echo "Deployed W2K preview — \$(tr -d '[:space:]' < "\$APP/VERSION")"
EOS
