#!/usr/bin/env bash
# Source from Beyond deploy scripts. Exit if this tree is Welcome 2 Kigali.
if [[ -f "$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)/WELCOME2KIGALI" ]]; then
  echo "REFUSING: this is the Welcome 2 Kigali repo."
  echo "Beyond lives in a different GitHub repo and /var/www/beyondtechworld."
  echo "W2K deploy: W2K_SSH_HOST=myvps W2K_REMOTE=/var/www/welcome2kigali-app/laravel-app bash tools/deploy-w2k-preview-files.sh"
  exit 1
fi
