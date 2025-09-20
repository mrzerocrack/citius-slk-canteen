#!/usr/bin/env bash
set -e

if ! command -v screen >/dev/null 2>&1; then
  echo "screen is not installed. Install with: sudo apt-get update && sudo apt-get install -y screen" >&2
  exit 1
fi

cd "$(dirname "$0")/.."

screen -dmS canteen_sync bash -lc 'php artisan canteen:sync-loop'
screen -dmS employee_rsync bash -lc 'bash run_tools/employee_photos_rsync.sh'

echo "Started screen sessions: canteen_sync, employee_rsync"
echo "Attach with: screen -r canteen_sync"

