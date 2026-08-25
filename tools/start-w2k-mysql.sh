#!/usr/bin/env bash
# Start a Welcome 2 Kigali-only MySQL on port 3308.
# Uses .mysql-data/ in this repo — never the Homebrew instance that holds Beyond.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DATA="$ROOT/.mysql-data"
SOCK="$DATA/mysql.sock"
PID="$DATA/mysqld.pid"
PORT=3308

mkdir -p "$DATA"

if [ ! -d "$DATA/mysql" ]; then
  echo "==> Initializing isolated MySQL datadir at $DATA"
  mysqld --initialize-insecure --datadir="$DATA" --user="$(whoami)"
fi

already=0
if [ -f "$PID" ]; then
  if kill -0 "$(cat "$PID")" 2>/dev/null; then
    already=1
  fi
fi

if [ "$already" -eq 1 ]; then
  echo "Welcome 2 Kigali MySQL already running (pid $(cat "$PID")) on :$PORT"
else
  echo "==> Starting mysqld on 127.0.0.1:$PORT"
  nohup mysqld \
    --datadir="$DATA" \
    --port="$PORT" \
    --bind-address=127.0.0.1 \
    --socket="$SOCK" \
    --pid-file="$PID" \
    --mysqlx=0 \
    --skip-networking=0 \
    >/tmp/w2k-mysqld.log 2>&1 &
  i=0
  while [ "$i" -lt 40 ]; do
    if mysqladmin --protocol=tcp -h 127.0.0.1 -P "$PORT" -u root ping --silent 2>/dev/null; then
      break
    fi
    i=$((i + 1))
    sleep 0.5
  done
fi

mysql --protocol=tcp -h 127.0.0.1 -P "$PORT" -u root <<'SQL'
CREATE DATABASE IF NOT EXISTS welcome2kigali CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'w2k'@'127.0.0.1' IDENTIFIED BY 'w2k_local';
CREATE USER IF NOT EXISTS 'w2k'@'localhost' IDENTIFIED BY 'w2k_local';
GRANT ALL PRIVILEGES ON welcome2kigali.* TO 'w2k'@'127.0.0.1';
GRANT ALL PRIVILEGES ON welcome2kigali.* TO 'w2k'@'localhost';
FLUSH PRIVILEGES;
SQL

echo ""
echo "Isolated database ready:"
echo "  host=127.0.0.1  port=$PORT  database=welcome2kigali  user=w2k"
echo "  Beyond Homebrew MySQL on :3306 is untouched."
