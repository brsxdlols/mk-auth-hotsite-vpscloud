#!/bin/sh
set -eu

if [ "$(id -u)" -ne 0 ]; then echo 'Execute como root.' >&2; exit 1; fi
ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
WEBROOT=${VPSCLOUD_WEBROOT:-/var/www}
case "$WEBROOT" in /var/www|/var/www/*) ;; *) echo 'WEBROOT inválido.' >&2; exit 1;; esac
STAMP=$(date +%Y%m%d_%H%M%S)
BACKUP=/opt/mk-auth/backups/vpscloud-hotsite/$STAMP
mkdir -p "$BACKUP"

php "$ROOT_DIR/installer/configure.php" --check
php "$ROOT_DIR/installer/configure.php" --read-theme > "$BACKUP/layhotsite.before.txt"
if [ -e "$WEBROOT/index.html" ] || [ -L "$WEBROOT/index.html" ]; then cp -aP "$WEBROOT/index.html" "$BACKUP/index.html.before"; fi
for target in layout/vpscloud midias_vpscloud; do
  if [ -e "$WEBROOT/$target" ]; then tar -C "$WEBROOT" -czf "$BACKUP/$(echo "$target" | tr / _).tar.gz" "$target"; fi
done
for file in abgs-data.php abgs-visitor.php abgs-signup.php cadastro-whatsapp.hhvm cadastro-sistema.php vpscloud-config.php; do
  if [ -f "$WEBROOT/$file" ]; then cp -a "$WEBROOT/$file" "$BACKUP/$file"; fi
done

rm -rf -- "$WEBROOT/layout/vpscloud" "$WEBROOT/midias_vpscloud"
install -d -m 0755 "$WEBROOT/layout/vpscloud" "$WEBROOT/midias_vpscloud"
cp -a "$ROOT_DIR/theme/layout/vpscloud/." "$WEBROOT/layout/vpscloud/"
cp -a "$ROOT_DIR/theme/midias_vpscloud/." "$WEBROOT/midias_vpscloud/"
for file in abgs-data.php abgs-visitor.php abgs-signup.php cadastro-whatsapp.hhvm cadastro-sistema.php; do
  install -m 0644 "$ROOT_DIR/theme/root/$file" "$WEBROOT/$file"
done
if [ ! -f "$WEBROOT/vpscloud-config.php" ]; then install -m 0644 "$ROOT_DIR/theme/root/vpscloud-config.php" "$WEBROOT/vpscloud-config.php"; fi
install -m 0755 "$ROOT_DIR/installer/vpscloud-cadastro-modo" /usr/local/sbin/vpscloud-cadastro-modo
find "$WEBROOT/layout/vpscloud" "$WEBROOT/midias_vpscloud" -type d -exec chmod 0755 {} \;
find "$WEBROOT/layout/vpscloud" "$WEBROOT/midias_vpscloud" -type f -exec chmod 0644 {} \;
php -l "$WEBROOT/abgs-data.php" >/dev/null
php -l "$WEBROOT/abgs-visitor.php" >/dev/null
php -l "$WEBROOT/abgs-signup.php" >/dev/null
php "$ROOT_DIR/installer/configure.php" --select-theme
ln -sfn "layout/vpscloud/index.html" "$WEBROOT/index.html"

echo "Backup: $BACKUP"
echo 'Tema VPS CLOUD instalado e selecionado com sucesso.'
echo 'Cadastro: vpscloud-cadastro-modo whatsapp|sistema'
