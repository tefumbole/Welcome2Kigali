#!/usr/bin/env bash
# Sync changed W2K Laravel files to the preview VPS and bump version each run.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
APP="$ROOT/laravel-app"
SSH_HOST="${W2K_SSH_HOST:-alphabridge-ts}"
REMOTE="${W2K_REMOTE:-/var/www/welcome2kigali-preview/laravel-app}"

if [[ "$REMOTE" == *beyondtechworld* ]]; then
  echo "REFUSING: this script only deploys Welcome 2 Kigali. Never write into Beyond."
  exit 1
fi

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
  app/Support/MomoNetwork.php
  app/Support/CafeMenuQr.php
  app/Support/MembershipIdScan.php
  app/Support/MembershipCountries.php
  app/Support/OnlineInvitationQr.php
  app/PawaPayDeposit.php
  app/MembershipPayment.php
  app/Services/PawaPayService.php
  app/Services/PawaPayPaymentService.php
  app/Services/StripeService.php
  app/Services/StripePaymentService.php
  app/StripeCheckout.php
  app/CustomerGroup.php
  app/Account.php
  app/Console/Kernel.php
  app/Console/Commands/ProcessMemberships.php
  app/Services/MembershipService.php
  app/Services/MembershipNotifier.php
  app/Http/Controllers/MembershipAdminController.php
  app/Http/Controllers/MembershipPublicController.php
  app/Http/Controllers/MembershipPosController.php
  app/Http/Controllers/SaleController.php
  app/Http/Controllers/CashRegisterController.php
  app/Http/Controllers/SiteContentController.php
  app/Http/Controllers/ProductController.php
  app/Http/Controllers/CustomerGroupController.php
  app/Http/Controllers/CartController.php
  app/Http/Controllers/HomeController.php
  app/Http/Controllers/LanguageController.php
  app/Http/Controllers/BeyondAuthController.php
  app/Http/Controllers/Auth/LoginController.php
  app/Http/Controllers/PawaPayCallbackController.php
  app/Http/Controllers/StripePaymentController.php
  app/Http/Controllers/BeyondController.php
  app/Http/Middleware/EncryptCookies.php
  app/Http/Middleware/VerifyCsrfToken.php
  app/Providers/AppServiceProvider.php
  app/Providers/RouteServiceProvider.php
  app/Http/Controllers/FrontendController.php
  app/Http/Controllers/HomeController.php
  app/Support/Letterhead.php
  app/Support/UserWorkspaces.php
  app/Support/SiteBrand.php
  app/Support/TaskPersonalization.php
  app/Support/AnnouncementPersonalization.php
  app/Services/BeyondAuthService.php
  app/Http/Middleware/Active.php
  app/Http/Controllers/SettingController.php
  app/Http/Controllers/ContractSettingsController.php
  app/Http/Controllers/ContractController.php
  app/Http/Controllers/ContractTemplateController.php
  app/Http/Controllers/EventContractController.php
  app/Http/Controllers/EventContractSigningController.php
  app/Http/Controllers/EventPaymentController.php
  app/Http/Controllers/UserSignatureController.php
  app/Services/TaskNotificationService.php
  app/Services/AnnouncementService.php
  app/Services/ApplicationNotifier.php
  app/Services/TimesheetService.php
  app/Services/EventContractService.php
  app/Services/Contracts/ContractInstanceService.php
  app/Services/Contracts/ContractBulkEngagementService.php
  app/Services/Contracts/ContractWorkflowService.php
  app/Console/Commands/SendContractSignatureReminders.php
  app/Support/WhatsAppPhone.php
  config/app.php
  database/migrations/2026_09_08_120000_rebrand_w2k_company_names.php
  database/migrations/2026_09_11_120000_ensure_w2k_catalog_settings.php
  database/migrations/2026_09_11_131500_add_remember_token_to_auth_tables.php
  app/Support/UserWorkspaces.php
  app/Http/Controllers/BeyondAuthController.php
  app/Support/SiteMenu.php
  app/Http/Controllers/UnitController.php
  app/Http/Controllers/CategoryController.php
  app/Support/InternCompliance.php
  app/Http/Controllers/WorkspaceController.php
  app/Http/Controllers/StaffPhoneAuthController.php
  app/Http/Middleware/EnsureInternCompliance.php
  app/Services/ApplicationService.php
  app/Services/PeopleDirectoryService.php
  app/Console/Commands/MergePhoneDuplicateUsers.php
  resources/views/beyond/auth/workspace.blade.php
  resources/views/beyond/partials/workspace_switcher.blade.php
  database/migrations/2026_09_08_100000_ensure_w2k_currency_and_letterhead.php
  resources/views/frontend/layout/main.blade.php
  app/Support/SiteI18n.php
  resources/lang/en/menu.php
  resources/lang/fr/menu.php
  resources/views/beyond/cart.blade.php
  resources/views/beyond/register-now.blade.php
  resources/views/beyond/auth/layout.blade.php
  resources/views/beyond/auth/login.blade.php
  database/migrations/2026_08_27_150000_create_membership_module.php
  database/migrations/2026_08_27_162000_add_is_default_debit_to_accounts_table.php
  database/migrations/2026_08_27_170000_ensure_default_pos_warehouse_biller.php
  database/migrations/2026_08_27_180000_create_pawapay_deposits_table.php
  database/migrations/2026_08_28_110000_create_stripe_checkouts_table.php
  database/migrations/2026_08_31_180000_add_vendor_id_to_orders_and_products_tables.php
  database/migrations/2026_09_02_200000_add_id_number_to_membership_applications.php
  database/migrations/2026_09_02_210000_add_id_dates_to_membership_applications.php
  database/migrations/2026_09_02_220000_add_nationality_to_membership_applications.php
  public/js/w2k-id-read.js
  routes/web.php
  routes/api.php
  config/services.php
  resources/lang/en/site.php
  resources/lang/fr/site.php
  resources/views/pdf/membership_confirmation.blade.php
  resources/views/layout/main.blade.php
  resources/views/beyond/layout.blade.php
  resources/views/beyond/home.blade.php
  resources/views/beyond/about.blade.php
  resources/views/beyond/contact.blade.php
  resources/views/beyond/menu.blade.php
  resources/views/beyond/menu-qr.blade.php
  resources/views/beyond/checkout.blade.php
  resources/views/beyond/membership/renew.blade.php
  resources/views/beyond/payments/pawapay_wait.blade.php
  resources/views/beyond/partials/contact_section.blade.php
  resources/views/beyond/partials/office_location.blade.php
  resources/views/site_content/index.blade.php
  resources/views/setting/general_setting.blade.php
  app/Support/SiteContent.php
  app/SiteSetting.php
  resources/views/leaders/index.blade.php
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
  resources/views/beyond \
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
# macOS rsync -a keeps 700 files/dirs that www-data cannot read
chown -R www-data:www-data "\$APP/app" "\$APP/resources" "\$APP/routes" "\$APP/database" "\$APP/config" "\$APP/public" "\$APP/VERSION" || true
find "\$APP/app" "\$APP/resources" "\$APP/routes" "\$APP/database" "\$APP/config" -type d -exec chmod 755 {} \\;
find "\$APP/app" "\$APP/resources" "\$APP/routes" "\$APP/database" "\$APP/config" -type f -exec chmod 644 {} \\;
chmod 644 "\$APP/VERSION" 2>/dev/null || true
chown -R www-data:www-data "\$APP/public/uploads/membership" "\$APP/storage" "\$APP/bootstrap/cache"
chmod -R ug+rwx "\$APP/public/uploads/membership" "\$APP/storage" "\$APP/bootstrap/cache"
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_08_27_150000_create_membership_module.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_08_27_162000_add_is_default_debit_to_accounts_table.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_08_27_170000_ensure_default_pos_warehouse_biller.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_08_27_180000_create_pawapay_deposits_table.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_08_28_110000_create_stripe_checkouts_table.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_08_31_180000_add_vendor_id_to_orders_and_products_tables.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_09_02_200000_add_id_number_to_membership_applications.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_09_02_210000_add_id_dates_to_membership_applications.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_09_02_220000_add_nationality_to_membership_applications.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_09_08_100000_ensure_w2k_currency_and_letterhead.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_09_08_120000_rebrand_w2k_company_names.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_09_11_120000_ensure_w2k_catalog_settings.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_09_11_131500_add_remember_token_to_auth_tables.php
sudo -u www-data php "\$APP/artisan" permission:cache-reset || true
sudo -u www-data php "\$APP/artisan" view:clear
sudo -u www-data php "\$APP/artisan" cache:clear
sudo -u www-data php "\$APP/artisan" config:clear
sudo -u www-data php -r "require '\$APP/vendor/autoload.php'; \\\$app = require '\$APP/bootstrap/app.php'; \\\$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap(); echo App\\\\Support\\\\AppVersion::syncToSettings().PHP_EOL;"
chown -R www-data:www-data "\$APP/storage" "\$APP/bootstrap/cache"
echo "Deployed W2K preview — \$(tr -d '[:space:]' < "\$APP/VERSION")"
EOS
