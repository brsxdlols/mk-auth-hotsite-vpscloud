#!/bin/sh
set -eu
[ "$(id -u)" -eq 0 ] || { echo 'Execute como root.' >&2; exit 1; }
ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
WEBROOT=$(readlink -f "${VPSCLOUD_WEBROOT:-/var/www}")
case "$WEBROOT" in /var/www|/var/www/*) ;; *) echo 'WEBROOT inválido.' >&2; exit 1;; esac
[ -d "$WEBROOT" ] || { echo 'Diretório do hotsite não encontrado.' >&2; exit 1; }
for cmd in curl tar install flock; do command -v "$cmd" >/dev/null || { echo "Comando necessário: $cmd" >&2; exit 1; }; done
exec 9>/var/lock/vpscloud-hotsite.lock
flock -n 9 || { echo 'Outra instalação está em andamento.' >&2; exit 1; }
PHP_BIN=''
for candidate in "${VPSCLOUD_PHP:-php}" /opt/php8/bin/php /usr/bin/php7.3; do
  if "$candidate" -r 'exit(PHP_VERSION_ID >= 70300 && extension_loaded("mysqli") ? 0 : 1);' >/dev/null 2>&1; then PHP_BIN=$candidate; break; fi
done
[ -n "$PHP_BIN" ] || { echo 'É necessário PHP 7.3+ com mysqli.' >&2; exit 1; }
ROOT_FILES='planos.php vpscloud-db.php vpscloud-layout.php abgs-data.php abgs-visitor.php abgs-signup.php cadastro-whatsapp.hhvm cadastro-sistema.php vpscloud-config.php'
for file in $ROOT_FILES; do
  [ -s "$ROOT_DIR/theme/root/$file" ] || { echo "Pacote incompleto: $file" >&2; exit 1; }
  "$PHP_BIN" -l "$ROOT_DIR/theme/root/$file" >/dev/null
done
[ -s "$ROOT_DIR/theme/layout/vpscloud/index.html" ]
[ -s "$ROOT_DIR/theme/midias_vpscloud/js/modern-vpscloud.js" ]
"$PHP_BIN" "$ROOT_DIR/installer/configure.php" --check
BACKUP=$(mktemp -d /opt/mk-auth/backups/vpscloud-hotsite/XXXXXXXX 2>/dev/null) || {
  mkdir -p /opt/mk-auth/backups/vpscloud-hotsite
  BACKUP=$(mktemp -d /opt/mk-auth/backups/vpscloud-hotsite/XXXXXXXX)
}
chmod 0700 "$BACKUP"
"$PHP_BIN" "$ROOT_DIR/installer/configure.php" --read-theme > "$BACKUP/theme.json"
TARGETS="index.html layout/vpscloud layout/layout-vpscloud-whatsapp layout/layout-vpscloud-sistema midias_vpscloud $ROOT_FILES"
: > "$BACKUP/existing.txt"
for target in $TARGETS; do
  if [ -e "$WEBROOT/$target" ] || [ -L "$WEBROOT/$target" ]; then echo "$target" >> "$BACKUP/existing.txt"; fi
done
tar -C "$WEBROOT" -czf "$BACKUP/files.tar.gz" -T "$BACKUP/existing.txt"
if [ -e /usr/local/sbin/vpscloud-cadastro-modo ]; then cp -a /usr/local/sbin/vpscloud-cadastro-modo "$BACKUP/cadastro-modo"; fi
CHANGED=0
rollback() {
  code=$?
  trap - EXIT HUP INT TERM
  if [ "$CHANGED" = 1 ]; then
    echo "Falha: restaurando backup $BACKUP" >&2
    for target in $TARGETS; do rm -rf -- "$WEBROOT/$target"; done
    tar -C "$WEBROOT" -xzf "$BACKUP/files.tar.gz"
    "$PHP_BIN" "$ROOT_DIR/installer/configure.php" --restore-theme "$BACKUP/theme.json" || echo 'Falha na restauração do tema; consulte o backup.' >&2
    if [ -e "$BACKUP/cadastro-modo" ]; then cp -a "$BACKUP/cadastro-modo" /usr/local/sbin/vpscloud-cadastro-modo; else rm -f /usr/local/sbin/vpscloud-cadastro-modo; fi
  fi
  exit "$code"
}
trap rollback EXIT
trap 'exit 1' HUP INT TERM
CHANGED=1
# Restore all backend files on every run, even when the visual theme already exists.
for file in $ROOT_FILES; do
  if [ "$file" = vpscloud-config.php ] && [ -f "$WEBROOT/$file" ]; then continue; fi
  install -m 0644 "$ROOT_DIR/theme/root/$file" "$WEBROOT/$file"
done
"$PHP_BIN" "$WEBROOT/abgs-data.php" > "$BACKUP/data-check.json"
"$PHP_BIN" "$ROOT_DIR/installer/verify-data.php" "$BACKUP/data-check.json"
CHECK_URL=${VPSCLOUD_CHECK_URL:-http://127.0.0.1}
curl --noproxy '*' --connect-timeout 5 --max-time 20 -fsS "${CHECK_URL%/}/abgs-data.php?vpscloud-check=1" -o "$BACKUP/http-check.json"
"$PHP_BIN" "$ROOT_DIR/installer/verify-data.php" "$BACKUP/http-check.json"
install -d -m 0755 "$WEBROOT/layout/vpscloud" "$WEBROOT/midias_vpscloud"
cp -a "$ROOT_DIR/theme/layout/vpscloud/." "$WEBROOT/layout/vpscloud/"
for layout in layout-vpscloud-whatsapp layout-vpscloud-sistema; do
  install -d -m 0755 "$WEBROOT/layout/$layout"
  cp -a "$ROOT_DIR/theme/layout/vpscloud/." "$WEBROOT/layout/$layout/"
done
cp -a "$ROOT_DIR/theme/midias_vpscloud/." "$WEBROOT/midias_vpscloud/"
find "$WEBROOT/layout/vpscloud" "$WEBROOT/layout/layout-vpscloud-whatsapp" "$WEBROOT/layout/layout-vpscloud-sistema" "$WEBROOT/midias_vpscloud" -type d -exec chmod 0755 {} \;
find "$WEBROOT/layout/vpscloud" "$WEBROOT/layout/layout-vpscloud-whatsapp" "$WEBROOT/layout/layout-vpscloud-sistema" "$WEBROOT/midias_vpscloud" -type f -exec chmod 0644 {} \;
install -m 0755 "$ROOT_DIR/installer/vpscloud-cadastro-modo" /usr/local/sbin/vpscloud-cadastro-modo
"$PHP_BIN" "$ROOT_DIR/installer/configure.php" --select-theme
SELECTED=$("$PHP_BIN" "$ROOT_DIR/installer/configure.php" --theme-name)
case "$SELECTED" in layout-vpscloud-sistema) MODE=sistema;; layout-vpscloud-whatsapp) MODE=whatsapp;; *) echo 'Layout inválido.' >&2; exit 1;; esac
ln -sfn "layout/$SELECTED/index.html" "$WEBROOT/index.html"
curl --noproxy '*' --connect-timeout 5 --max-time 20 -fsS "${CHECK_URL%/}/abgs-data.php?vpscloud-check=layout" -o "$BACKUP/layout-check.json"
"$PHP_BIN" "$ROOT_DIR/installer/verify-data.php" "$BACKUP/layout-check.json" "$MODE"
echo "Layout selecionado: $SELECTED"
CHANGED=0
echo "Backup: $BACKUP"
echo 'Tema VPS CLOUD instalado e validado com sucesso.'
echo 'Escolha o cadastro em Hotsite > Layout: layout-vpscloud-whatsapp ou layout-vpscloud-sistema.'
