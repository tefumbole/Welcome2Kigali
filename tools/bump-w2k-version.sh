#!/usr/bin/env bash
# Bump laravel-app/VERSION and sync W2K_V_x.y.z to Node/React/API constants.
# Called before preview deploys; pre-commit hook uses the same semver rules.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
FILE="$ROOT/laravel-app/VERSION"
[ -f "$FILE" ] || exit 0

current="$(tr -d '[:space:]' < "$FILE")"
case "$current" in
  [0-9]*.[0-9]*.[0-9]*) ;;
  *) echo "Skip bump: unrecognized VERSION ($current)" >&2; exit 0 ;;
esac

major="${current%%.*}"
rest="${current#*.}"
minor="${rest%%.*}"
patch="${rest#*.}"

patch=$((patch + 1))
if [ "$patch" -gt 10 ]; then
  patch=1
  minor=$((minor + 1))
fi
if [ "$minor" -gt 10 ]; then
  minor=2
  major=$((major + 1))
fi

next="${major}.${minor}.${patch}"
printf '%s\n' "$next" > "$FILE"

ERP="W2K_V_${next}"
for js in "$ROOT/src/constants/appVersion.js" "$ROOT/apps/api/src/constants/appVersion.js"; do
  [ -f "$js" ] || continue
  sed -i '' "s/export const APP_VERSION = 'W2K_V_[^']*';/export const APP_VERSION = '${ERP}';/" "$js" 2>/dev/null \
    || sed -i "s/export const APP_VERSION = 'W2K_V_[^']*';/export const APP_VERSION = '${ERP}';/" "$js"
done

echo "[bump-w2k-version] ${current} -> ${next} (${ERP})"
