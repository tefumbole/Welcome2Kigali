#!/usr/bin/env bash
# Legacy helper — prefer .githooks/pre-commit (laravel-app/VERSION).
# Scheme: 1.1.9 → 1.1.10 → 1.2.1; after 1.10.10 → 2.2.1.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
VERSION_FILE="$ROOT_DIR/VERSION"

if [[ ! -f "$VERSION_FILE" ]]; then
    echo "1.1.9" > "$VERSION_FILE"
fi

current="$(tr -d '[:space:]' < "$VERSION_FILE")"

if [[ $current =~ ^([0-9]+)\.([0-9]+)\.([0-9]+)$ ]]; then
    major="${BASH_REMATCH[1]}"
    minor="${BASH_REMATCH[2]}"
    patch="${BASH_REMATCH[3]}"
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
    echo "$next" > "$VERSION_FILE"
    echo "Version bumped to $next"
elif [[ $current =~ V\.?([0-9]+)\.([0-9]+)\.([0-9]+) ]]; then
    major="${BASH_REMATCH[1]}"
    minor="${BASH_REMATCH[2]}"
    patch="${BASH_REMATCH[3]}"
    patch=$((patch + 1))
    if [ "$patch" -gt 10 ]; then
        patch=1
        minor=$((minor + 1))
    fi
    if [ "$minor" -gt 10 ]; then
        minor=2
        major=$((major + 1))
    fi
    next="W2K_V_${major}.${minor}.${patch}"
    echo "$next" > "$VERSION_FILE"
    echo "Version bumped to $next"
else
    echo "Could not parse version from: $current" >&2
    exit 1
fi
