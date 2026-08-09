#!/bin/sh
set -eu
if [ "$(id -u)" -ne 0 ]; then echo 'Execute como root.' >&2; exit 1; fi
ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
WEBROOT=/var/www
STAMP=$(date +%Y%m%d_%H%M%S)
BACKUP=/opt/mk-auth/backups/vpscloud-hotsite/$STAMP
mkdir -p "$BACKUP"
for target in layout/vpscloud midias_vpscloud vpscloud-api; do
  if [ -e "$WEBROOT/$target" ]; then tar -C "$WEBROOT" -czf "$BACKUP/$(echo "$target" | tr / _).tar.gz" "$target"; fi
done
timeout 20 php "$ROOT_DIR/installer/configure.php" --read-theme > "$BACKUP/layhotsite.before.txt" || { echo 'Banco do MK-Auth indisponível; instalação cancelada sem trocar o tema.' >&2; exit 1; }
install -d -m 0755 "$WEBROOT/layout/vpscloud" "$WEBROOT/midias_vpscloud/css" "$WEBROOT/midias_vpscloud/js" "$WEBROOT/midias_vpscloud/images" "$WEBROOT/vpscloud-api"
cp -a "$ROOT_DIR/theme/layout/vpscloud/." "$WEBROOT/layout/vpscloud/"
cp -a "$ROOT_DIR/theme/midias_vpscloud/." "$WEBROOT/midias_vpscloud/"
cp -a "$ROOT_DIR/theme/vpscloud-api/." "$WEBROOT/vpscloud-api/"
for page in planos cadastro cobertura empresa suporte faq contato privacidade; do cp -f "$WEBROOT/layout/vpscloud/index.html" "$WEBROOT/layout/vpscloud/$page.html"; done
find "$WEBROOT/layout/vpscloud" "$WEBROOT/midias_vpscloud" "$WEBROOT/vpscloud-api" -type d -exec chmod 0755 {} \;
find "$WEBROOT/layout/vpscloud" "$WEBROOT/midias_vpscloud" "$WEBROOT/vpscloud-api" -type f -exec chmod 0644 {} \;
install -m 0755 "$ROOT_DIR/installer/configure.php" /opt/mk-auth/scripts/vpscloud-hotsite-sync.php
timeout 30 php /opt/mk-auth/scripts/vpscloud-hotsite-sync.php
printf '%s\n' '*/5 * * * * root /usr/bin/php /opt/mk-auth/scripts/vpscloud-hotsite-sync.php --sync-only >/dev/null 2>&1' > /etc/cron.d/vpscloud-hotsite
chmod 0644 /etc/cron.d/vpscloud-hotsite
rm -f "$WEBROOT/vpscloud-api/config.php"
echo "Backup: $BACKUP"
echo 'Tema VPS CLOUD instalado com sucesso.'
