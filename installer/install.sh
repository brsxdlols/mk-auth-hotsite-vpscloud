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
if command -v mysql >/dev/null 2>&1; then
  php -r "require '/opt/mk-auth/include/conexao.php'; if(isset(\$LOADMYSQL)){\$r=\$LOADMYSQL->query(\"SELECT valor FROM sis_opcao WHERE nome='layhotsite' LIMIT 1\"); echo (\$r&&\$r->num_rows?\$r->fetch_row()[0]:'').PHP_EOL;}" > "$BACKUP/layhotsite.before.txt"
fi
install -d -m 0755 "$WEBROOT/layout/vpscloud" "$WEBROOT/midias_vpscloud/css" "$WEBROOT/midias_vpscloud/js" "$WEBROOT/midias_vpscloud/images" "$WEBROOT/vpscloud-api"
cp -a "$ROOT_DIR/theme/layout/vpscloud/." "$WEBROOT/layout/vpscloud/"
cp -a "$ROOT_DIR/theme/midias_vpscloud/." "$WEBROOT/midias_vpscloud/"
cp -a "$ROOT_DIR/theme/vpscloud-api/." "$WEBROOT/vpscloud-api/"
for page in planos cadastro cobertura empresa suporte faq contato privacidade; do cp -f "$WEBROOT/layout/vpscloud/index.html" "$WEBROOT/layout/vpscloud/$page.html"; done
find "$WEBROOT/layout/vpscloud" "$WEBROOT/midias_vpscloud" "$WEBROOT/vpscloud-api" -type d -exec chmod 0755 {} \;
find "$WEBROOT/layout/vpscloud" "$WEBROOT/midias_vpscloud" "$WEBROOT/vpscloud-api" -type f -exec chmod 0644 {} \;
install -m 0755 "$ROOT_DIR/installer/configure.php" /opt/mk-auth/scripts/vpscloud-hotsite-sync.php
php /opt/mk-auth/scripts/vpscloud-hotsite-sync.php
printf '%s\n' '*/5 * * * * root /usr/bin/php /opt/mk-auth/scripts/vpscloud-hotsite-sync.php --sync-only >/dev/null 2>&1' > /etc/cron.d/vpscloud-hotsite
chmod 0644 /etc/cron.d/vpscloud-hotsite
rm -f "$WEBROOT/vpscloud-api/config.php"
echo "Backup: $BACKUP"
echo 'Tema VPS CLOUD instalado com sucesso.'
