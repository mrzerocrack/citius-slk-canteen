## Canteen Sync + Photo Rsync Daemon

This folder contains scripts and unit files to run two background jobs every 10 minutes without stopping:
- An Artisan loop that POSTs to your sync endpoints with `key_code=T()tt3nh@m`.
- An rsync loop that mirrors employee photos from the remote host.

### Components
- `app/Console/Commands/CanteenSyncLoop.php` — Artisan command `canteen:sync-loop`.
- `run_tools/employee_photos_rsync.sh` — Infinite rsync loop (10 min interval by default).
- `run_tools/rsync.env.example` — Example env file for rsync password/interval.
- `run_tools/canteen-sync-loop.service` — systemd service for the Artisan loop.
- `run_tools/employee-photos-rsync.service` — systemd service for the rsync loop.
- `run_tools/start_with_tmux.sh` — Optional tmux launcher.
- `run_tools/start_with_screen.sh` — Optional GNU screen launcher.

### Laravel sync loop
- Command: `php artisan canteen:sync-loop`
- Default interval: 600 seconds (10 minutes).
- Targets (by default):
  - http://sks.canteen/api/sync_canteen
  - http://sks.canteen/api/sync_slp
  - http://sks.canteen/api/sync_employee
  - http://sks.canteen/api/sync_employee_cc
  - http://sks.canteen/api/sync_log
- Env overrides (optional; set in `.env` or via systemd Environment):
  - `SYNC_HOST_URL` (default `http://sks.canteen`)
  - `SYNC_PREFIX` (default `api`; set empty to call routes without `/api`)
  - `SYNC_INTERVAL` (seconds; default `600`)

### Employee photos rsync loop
- Script: `bash run_tools/employee_photos_rsync.sh`
- Default interval: 600 seconds (override with `RSYNC_INTERVAL`)
- Source: `root@d.ipp-kalteng1.com:/var/www/citius-slk/public/assets/images/employee/`
- Dest: `/var/www/citius-slk-canteen/public/assets/images/employee/`

#### Authentication options
1) Recommended: SSH key (no password prompts)
- Generate an SSH key (only once):
  - `ssh-keygen -t ed25519 -C "canteen-sync"`
- Copy the public key to the remote host:
  - `ssh-copy-id root@d.ipp-kalteng1.com`
- Test:
  - `rsync -avh --delete root@d.ipp-kalteng1.com:/var/www/citius-slk/public/assets/images/employee/ /var/www/citius-slk-canteen/public/assets/images/employee/`

2) Password-based (not recommended):
- Install `sshpass` (requires sudo):
  - `sudo apt-get update && sudo apt-get install -y sshpass`
- Create `run_tools/rsync.env` from the example and set:
  - `SSH_PASSWORD=!Zionis123`
- The script will use `sshpass` automatically if present. Without `sshpass`, it falls back to key-based or interactive auth.

### Run as services (systemd)
Requires root to install services.

1) Copy unit files:
- `sudo cp run_tools/canteen-sync-loop.service /etc/systemd/system/`
- `sudo cp run_tools/employee-photos-rsync.service /etc/systemd/system/`

2) Adjust the user in the unit files if needed:
- Edit `User=` to the user that owns the project and has permissions to write destination folders (e.g., `kalteng1-server-vm04`).

3) Optional: Provide rsync password via env file (if using password auth):
- `cp run_tools/rsync.env.example run_tools/rsync.env`
- Edit `run_tools/rsync.env` and set `SSH_PASSWORD=!Zionis123`.
- Ensure `run_tools/rsync.env` is readable by the service user and not committed (already gitignored).

4) Reload and enable services:
- `sudo systemctl daemon-reload`
- `sudo systemctl enable --now canteen-sync-loop.service`
- `sudo systemctl enable --now employee-photos-rsync.service`

5) Check status and logs:
- `systemctl status canteen-sync-loop.service`
- `journalctl -u canteen-sync-loop.service -f`
- `systemctl status employee-photos-rsync.service`
- `journalctl -u employee-photos-rsync.service -f`

### Optional: run with tmux or screen
If you prefer not to use systemd:
- Install tmux: `sudo apt-get install -y tmux`
- Run: `bash run_tools/start_with_tmux.sh`
- Install screen: `sudo apt-get install -y screen`
- Run: `bash run_tools/start_with_screen.sh`

### Notes
- Storing passwords in files is risky. Prefer SSH keys whenever possible.
- The Artisan command logs failures to `storage/logs/laravel.log`.
- Both loops are infinite by design. Use systemd `Restart=always` to keep them alive.

