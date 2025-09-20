#!/usr/bin/env bash
set -Eeuo pipefail

# Runs every 10 minutes without stopping to mirror employee photos

SRC="root@d.ipp-kalteng1.com:/var/www/citius-slk/public/assets/images/employee/"
DEST="/var/www/citius-slk-canteen/public/assets/images/employee/"
INTERVAL="${RSYNC_INTERVAL:-600}"
RSYNC_SSH_OPTS="${RSYNC_SSH_OPTS:-ssh -o StrictHostKeyChecking=accept-new}"

# Optional password can be provided via environment or env file
ENV_FILE="$(dirname "$0")/rsync.env"
if [[ -f "$ENV_FILE" ]]; then
  # shellcheck source=/dev/null
  . "$ENV_FILE"
fi

mkdir -p "$DEST"

warned_missing_sshpass=false
ASKPASS_HELPER=""

echo "Starting employee photos rsync loop (interval: ${INTERVAL}s)"
while true; do
  echo "["$(date -Is)"] Running rsync..."
  if [[ -n "${SSH_PASSWORD:-}" ]] && command -v sshpass >/dev/null 2>&1; then
    SSH_ASKPASS_DISABLE=1 sshpass -p "$SSH_PASSWORD" rsync -e "$RSYNC_SSH_OPTS" -avh --delete "$SRC" "$DEST"
  else
    if [[ -n "${SSH_PASSWORD:-}" ]] && command -v setsid >/dev/null 2>&1; then
      # Fallback using SSH_ASKPASS (no dependency on sshpass). Requires no controlling TTY.
      if [[ -z "$ASKPASS_HELPER" ]]; then
        ASKPASS_HELPER="$(mktemp)"
        cat > "$ASKPASS_HELPER" <<'EOF'
#!/usr/bin/env bash
exec bash -c 'echo -n "$SSH_PASSWORD"'
EOF
        chmod 700 "$ASKPASS_HELPER"
        trap 'rm -f "$ASKPASS_HELPER"' EXIT
      fi
      DISPLAY=none SSH_ASKPASS="$ASKPASS_HELPER" SSH_ASKPASS_REQUIRE=force \
        setsid rsync -e "$RSYNC_SSH_OPTS" -avh --delete "$SRC" "$DEST"
    else
      if [[ -n "${SSH_PASSWORD:-}" ]] && [[ "$warned_missing_sshpass" = false ]]; then
        echo "[warn] SSH_PASSWORD provided but neither sshpass nor setsid ASKPASS fallback available. Falling back to key-based SSH or interactive password." >&2
        warned_missing_sshpass=true
      fi
      rsync -e "$RSYNC_SSH_OPTS" -avh --delete "$SRC" "$DEST"
    fi
  fi
  echo "["$(date -Is)"] Done. Sleeping..."
  sleep "$INTERVAL"
done
