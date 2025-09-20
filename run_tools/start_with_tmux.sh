#!/usr/bin/env bash
set -e

if ! command -v tmux >/dev/null 2>&1; then
  echo "tmux is not installed. Install with: sudo apt-get update && sudo apt-get install -y tmux" >&2
  exit 1
fi

cd "$(dirname "$0")/.."

tmux new-session -d -s canteen_sync "php artisan canteen:sync-loop"
tmux new-session -d -s employee_rsync "bash run_tools/employee_photos_rsync.sh"

echo "Started tmux sessions: canteen_sync, employee_rsync"
echo "Attach with: tmux attach -t canteen_sync"

