#!/usr/bin/env bash
# Creates a local MySQL database for Welcome 2 Kigali only (not Beyond).
# Prefer: docker compose up -d  (port 3308, database welcome2kigali)
# Homebrew alternative: brew install mysql && brew services start mysql
set -euo pipefail

DB_NAME=welcome2kigali
DB_USER=w2k
DB_PASS=w2k_local

echo "This script creates database '$DB_NAME' and user '$DB_USER' on local MySQL."
echo "You will be prompted for your MySQL root password."
echo "Do not run this against Beyond's Hostinger or VPS database."
echo ""

mysql -u root -p <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

echo ""
echo "Local Welcome 2 Kigali database ready. Next steps:"
echo "  cp apps/api/.env.local.example apps/api/.env"
echo "  cp laravel-app/.env.example laravel-app/.env   # then php artisan key:generate"
echo "  npm run db:migrate"
echo "  cd laravel-app && php artisan migrate && php artisan db:seed"
echo "  npm run dev:api    # terminal 1"
echo "  npm run dev        # terminal 2"
echo ""
echo "Login: admin@welcome2kigali.local / ChangeMe@123456"
