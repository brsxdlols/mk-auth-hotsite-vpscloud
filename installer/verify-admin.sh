#!/bin/sh
# Return failure to the caller; do not change native files or theme selection.
set -eu
CHECK_URL=${1%/}
BACKUP=$2
curl --noproxy '*' --connect-timeout 5 --max-time 20 -fsS "$CHECK_URL/admin/hotsite_layout.hhvm" -D "$BACKUP/admin-headers.txt" -o "$BACKUP/admin-check.html" || exit 1
[ -s "$BACKUP/admin-check.html" ] || exit 1
grep -qi '^X-VPSCloud-Layout: enabled' "$BACKUP/admin-headers.txt"
