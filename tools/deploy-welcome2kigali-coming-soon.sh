#!/usr/bin/env bash
# Deploy the Welcome 2 Kigali Coming Soon page to this VPS.
# Usage ON the VPS: bash tools/deploy-welcome2kigali-coming-soon.sh
# Or from Mac after rsync: ssh myvps 'bash /var/www/welcome2kigali-src/tools/deploy-welcome2kigali-coming-soon.sh'
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEST="${W2K_WEBROOT:-/var/www/welcome2kigali}"
AVAILABLE="/etc/nginx/sites-available/welcome2kigali"
ENABLED="/etc/nginx/sites-enabled/welcome2kigali"

# Never write into Beyond / other live apps.
case "$DEST" in
  /var/www/beyondtechworld|/var/www/beyondtechworld/*|/var/www/alphabridge|/var/www/alphabridge/*)
    echo "REFUSING: destination $DEST would overwrite another site."
    exit 1
    ;;
esac
if [[ "$ROOT" == /var/www/beyondtechworld* ]]; then
  echo "REFUSING: deploy script invoked from Beyond tree."
  exit 1
fi

echo "==> Install Coming Soon files to $DEST"
mkdir -p "$DEST"
install -m 644 "$ROOT/coming-soon/index.html" "$DEST/index.html"
install -m 644 "$ROOT/coming-soon/w2k-logo.png" "$DEST/w2k-logo.png"

echo "==> Nginx HTTP vhost (SSL swapped in after certbot)"
if [[ -f /etc/letsencrypt/live/welcome2kigali.net/fullchain.pem ]]; then
  install -m 644 "$ROOT/tools/nginx/welcome2kigali.conf" "$AVAILABLE"
else
  install -m 644 "$ROOT/tools/nginx/welcome2kigali-http.conf" "$AVAILABLE"
fi
ln -sf "$AVAILABLE" "$ENABLED"
nginx -t
systemctl reload nginx

if [[ ! -f /etc/letsencrypt/live/welcome2kigali.net/fullchain.pem ]]; then
  echo "==> Request Let's Encrypt certificate"
  mkdir -p /var/www/letsencrypt
  certbot certonly --webroot -w /var/www/letsencrypt \
    -d welcome2kigali.net -d www.welcome2kigali.net \
    --non-interactive --agree-tos -m hello@welcome2kigali.net \
    || echo "    certbot skipped or failed — point DNS A/AAAA to this VPS, then re-run"
  if [[ -f /etc/letsencrypt/live/welcome2kigali.net/fullchain.pem ]]; then
    install -m 644 "$ROOT/tools/nginx/welcome2kigali.conf" "$AVAILABLE"
    nginx -t && systemctl reload nginx
  fi
fi

echo ""
echo "Coming Soon is at $DEST"
echo "  http://welcome2kigali.net   (Host header only — does not steal Beyond)"
echo "  https://welcome2kigali.net  (after DNS + SSL)"
echo "  http://127.0.0.1:3008      (W2K-only port; Beyond stays on :3004)"
