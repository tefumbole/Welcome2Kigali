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
  app/Support/VisitorLocale.php
  app/Support/MessageSerial.php
  app/ContactMessage.php
  app/Customer.php
  app/Http/Controllers/PublicRentalController.php
  app/Services/ContactInquiryService.php
  app/Services/Messaging/NotificationRouter.php
  database/migrations/2026_09_18_103000_create_institutional_messages.php
  database/migrations/2026_09_18_120000_create_wa_message_serials_table.php
  database/migrations/2026_09_18_203000_create_stock_durations_table.php
  database/migrations/2026_09_21_133000_add_commission_to_general_settings.php
  resources/views/mail/contact_message.blade.php
  app/Support/AppUrl.php
  app/Support/SchemaColumns.php
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
  app/Http/Controllers/PurchaseController.php
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
  app/Providers/AuthServiceProvider.php
  app/Providers/RouteServiceProvider.php
  database/migrations/2026_09_21_153500_grant_superadmin_all_permissions.php
  app/Http/Controllers/AnnouncementController.php
  app/Jobs/SendOnlineInvitationJob.php
  app/Http/Controllers/OnlineInvitationInvitationController.php
  app/Console/Commands/SendOnlineInvitationReminders.php
  app/Http/Controllers/Controller.php
  app/Http/Controllers/LetterController.php
  app/Http/Controllers/ShopController.php
  app/Http/Controllers/FrontendController.php
  app/Services/EventReminderService.php
  app/Console/Commands/RentalReturnReminderCron.php
  app/Http/Controllers/HomeController.php
  app/Support/Letterhead.php
  app/Support/UserWorkspaces.php
  app/Support/SiteBrand.php
  app/Support/TaskPersonalization.php
  app/Support/AnnouncementPersonalization.php
  app/Services/BeyondAuthService.php
  app/Http/Middleware/Active.php
  app/Http/Controllers/SettingController.php
  app/Http/Controllers/UserController.php
  app/Http/Controllers/RoleController.php
  app/Http/Controllers/AccountsController.php
  app/Http/Controllers/CurrencyController.php
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
  database/migrations/2026_09_17_120000_ensure_w2k_module_columns.php
  app/Quotation.php
  app/Expense.php
  app/AssetExpense.php
  app/Http/Controllers/OrderController.php
  app/Http/Controllers/ExpenseController.php
  app/Http/Controllers/ReturnController.php
  app/Http/Controllers/AssetController.php
  app/Http/Controllers/BookingController.php
  app/Http/Controllers/QuotationController.php
  app/Http/Controllers/RentalContractController.php
  app/Http/Controllers/BookingGoodsReceiptController.php
  app/Http/Controllers/BookingReminderController.php
  app/Http/Controllers/DeliveryController.php
  app/Http/Controllers/TrainingController.php
  app/Http/Controllers/PublicPermissionController.php
  app/BeyondUser.php
  resources/views/mail/quotation_details.blade.php
  resources/views/mail/booking_details.blade.php
  app/Console/Commands/ProcessContractReminders.php
  resources/views/contracts/sign.blade.php
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
  resources/lang/rw/site.php
  resources/lang/rw/menu.php
  resources/lang/en/whatsapp.php
  resources/lang/fr/whatsapp.php
  resources/lang/rw/whatsapp.php
  resources/lang/en/mail.php
  resources/lang/fr/mail.php
  resources/lang/rw/mail.php
  database/migrations/2026_09_18_180000_add_preferred_locale_to_visitor_records.php
  database/migrations/2026_09_22_113500_add_preferred_locale_to_be_users.php
  resources/views/pdf/membership_confirmation.blade.php
  resources/views/layout/main.blade.php
  resources/views/beyond/layout.blade.php
  resources/views/beyond/home.blade.php
  resources/views/beyond/about.blade.php
  resources/views/beyond/partials/hero.blade.php
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
  resources/views/sale/index.blade.php
  resources/views/sale/create.blade.php
  resources/views/booking/index.blade.php
  resources/views/booking/online-index.blade.php
  resources/views/booking/daily_booking.blade.php
  resources/views/setting/reward_point_setting.blade.php
  resources/views/sale/edit.blade.php
  resources/views/sale/create_sale.blade.php
  resources/views/booking/pos.blade.php
  resources/views/booking/create.blade.php
  resources/views/membership/dashboard.blade.php
  resources/views/membership/partials/tabs.blade.php
  resources/views/task_manager/partials/tabs.blade.php
  resources/views/course_manager/partials/styles.blade.php
  resources/views/booking/invoice.blade.php
  resources/views/sale/invoice.blade.php
  resources/lang/en/file.php
  resources/lang/fr/file.php
  resources/lang/rw/file.php
  resources/lang/en/sidebar.php
  resources/lang/fr/sidebar.php
  resources/lang/rw/sidebar.php
  resources/views/pdf/booking_pdf.blade.php
  resources/views/pdf/rent_pdf.blade.php
  resources/views/pdf/sale_pdf.blade.php
  resources/views/pdf/letter_pdf.blade.php
  resources/views/pdf/letter_download_pdf.blade.php
  resources/views/pdf/cc_letter_pdf.blade.php
  resources/views/pdf/multiple_letter_download_pdf.blade.php
  resources/views/mail/letter_details.blade.php
  resources/views/letter/letter_body.blade.php
  resources/views/pdf/partials/_letter_branded_open.blade.php
  resources/views/pdf/partials/_letter_branded_close.blade.php
  resources/views/pdf/partials/_letter_branded_styles.blade.php
  resources/views/pdf/partials/_letter_branded_inner.blade.php
  resources/views/booking/edit.blade.php
  resources/views/booking/create_sale.blade.php
  resources/views/product/create.blade.php
  resources/views/product/index.blade.php
  app/Http/Controllers/HelpController.php
  resources/views/help/index.blade.php
  resources/views/product/edit.blade.php
  public/uploads/membership/.gitkeep
  public/branding/footer-swoosh.svg
  public/branding/footer-wave.svg
  public/branding/w2k-landing.png
  public/branding/w2k-landing.jpg
  public/branding/w2k-landing@2x.jpg
  public/css/custom.css
  public/css/w2k-coffee-admin.css
  public/css/w2k-mobile.css
  public/js/front.js
  resources/views/purchase/index.blade.php
  resources/views/beyond/events.blade.php
  resources/views/report/average_report.blade.php
  resources/views/events/contracts/sign.blade.php
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
  resources/views/help \
  public/branding/help \
  "$SSH_HOST:$REMOTE/"

# Demo-lock blades not always listed in FILES
/usr/bin/rsync -az --relative \
  resources/views/adjustment/index.blade.php \
  resources/views/attendance/index.blade.php \
  resources/views/biller/index.blade.php \
  resources/views/brand/create.blade.php \
  resources/views/category/create.blade.php \
  resources/views/coupon/index.blade.php \
  resources/views/customer/index.blade.php \
  resources/views/customer/index2.blade.php \
  resources/views/customer_group/create.blade.php \
  resources/views/customer_group/deposits.blade.php \
  resources/views/customer_group/payments.blade.php \
  resources/views/delivery/index.blade.php \
  resources/views/department/index.blade.php \
  resources/views/employee/index.blade.php \
  resources/views/expense/asset.blade.php \
  resources/views/expense/index.blade.php \
  resources/views/expense_category/index.blade.php \
  resources/views/fixed_asset/asset/activity.blade.php \
  resources/views/fixed_asset/asset/activity_repair.blade.php \
  resources/views/gift_card/index.blade.php \
  resources/views/holiday/index.blade.php \
  resources/views/letter_category/index.blade.php \
  resources/views/money_transfer/index.blade.php \
  resources/views/payment/create.blade.php \
  resources/views/payment/customer-data.blade.php \
  resources/views/payment/deposits.blade.php \
  resources/views/payroll/index.blade.php \
  resources/views/product/vendor_index.blade.php \
  resources/views/quotation/index.blade.php \
  resources/views/report/patient_due_report.blade.php \
  resources/views/return/index.blade.php \
  resources/views/return_purchase/index.blade.php \
  resources/views/supplier/index.blade.php \
  resources/views/tax/create.blade.php \
  resources/views/transfer/index.blade.php \
  resources/views/unit/create.blade.php \
  resources/views/user/index.blade.php \
  resources/views/warehouse/create.blade.php \
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
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_09_17_120000_ensure_w2k_module_columns.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_09_18_103000_create_institutional_messages.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_09_18_120000_create_wa_message_serials_table.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_09_18_180000_add_preferred_locale_to_visitor_records.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_09_22_113500_add_preferred_locale_to_be_users.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_09_18_203000_create_stock_durations_table.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_09_21_133000_add_commission_to_general_settings.php
sudo -u www-data php "\$APP/artisan" migrate --force --path=database/migrations/2026_09_21_153500_grant_superadmin_all_permissions.php
sudo -u www-data php "\$APP/artisan" permission:cache-reset || true
sudo -u www-data php "\$APP/artisan" view:clear
sudo -u www-data php "\$APP/artisan" cache:clear
sudo -u www-data php "\$APP/artisan" config:clear
sudo -u www-data php -r "require '\$APP/vendor/autoload.php'; \\\$app = require '\$APP/bootstrap/app.php'; \\\$app->make('Illuminate\\\\Contracts\\\\Console\\\\Kernel')->bootstrap(); echo App\\\\Support\\\\AppVersion::syncToSettings().PHP_EOL;"
chown -R www-data:www-data "\$APP/storage" "\$APP/bootstrap/cache"
echo "Deployed W2K preview — \$(tr -d '[:space:]' < "\$APP/VERSION")"
EOS
