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
ROOT_FILES='vpscloud-favicon.php vpscloud-admin-layout.php vpscloud-home.php vpscloud-meta.php planos.php vpscloud-db.php vpscloud-layout.php abgs-data.php abgs-visitor.php abgs-signup.php cadastro-whatsapp.hhvm cadastro-sistema.php vpscloud-config.php'
for file in $ROOT_FILES; do
  [ -s "$ROOT_DIR/theme/root/$file" ] || { echo "Pacote incompleto: $file" >&2; exit 1; }
  "$PHP_BIN" -l "$ROOT_DIR/theme/root/$file" >/dev/null
done
[ -s "$ROOT_DIR/theme/layout/vpscloud/index.html" ]
[ -s "$ROOT_DIR/theme/midias_vpscloud/js/modern-vpscloud.js" ]
PRESERVE_THEME=0
[ ! -d "$WEBROOT/layout/layout-vpscloud-sistema" ] || PRESERVE_THEME=1
SHORTCUT_THEME=''
if [ -L "$WEBROOT/index.html" ]; then
  case "$(readlink "$WEBROOT/index.html")" in
    layout/layout-vpscloud-sistema/index.html|"$WEBROOT"/layout/layout-vpscloud-sistema/index.html)
      SHORTCUT_THEME=layout-vpscloud-sistema;;
    layout/layout-vpscloud-whatsapp/index.html|"$WEBROOT"/layout/layout-vpscloud-whatsapp/index.html)
      SHORTCUT_THEME=layout-vpscloud-whatsapp;;
  esac
fi
"$PHP_BIN" "$ROOT_DIR/installer/configure.php" --check
BACKUP=$(mktemp -d /opt/mk-auth/backups/vpscloud-hotsite/XXXXXXXX 2>/dev/null) || {
  mkdir -p /opt/mk-auth/backups/vpscloud-hotsite
  BACKUP=$(mktemp -d /opt/mk-auth/backups/vpscloud-hotsite/XXXXXXXX)
}
chmod 0700 "$BACKUP"
"$PHP_BIN" "$ROOT_DIR/installer/configure.php" --read-theme > "$BACKUP/theme.json"
LAYOUTS=$("$PHP_BIN" "$ROOT_DIR/installer/configure.php" --list-layouts)
TARGETS=".htaccess index.html layout/vpscloud midias_vpscloud $ROOT_FILES"
for layout in $LAYOUTS; do TARGETS="$TARGETS layout/$layout"; done
: > "$BACKUP/existing.txt"
for target in $TARGETS; do
  if [ -e "$WEBROOT/$target" ] || [ -L "$WEBROOT/$target" ]; then echo "$target" >> "$BACKUP/existing.txt"; fi
done
tar -C "$WEBROOT" -czf "$BACKUP/files.tar.gz" -T "$BACKUP/existing.txt"
if [ -e /usr/local/sbin/vpscloud-cadastro-modo ]; then cp -a /usr/local/sbin/vpscloud-cadastro-modo "$BACKUP/cadastro-modo"; fi
cp -p /opt/mk-auth/admin/hotsite_layout.hhvm "$BACKUP/admin-layout.hhvm"
if [ -f /opt/mk-auth/admin/.htaccess ]; then cp -p /opt/mk-auth/admin/.htaccess "$BACKUP/admin-htaccess"; fi
CHANGED=0
rollback() {
  code=$?
  trap - EXIT HUP INT TERM
  if [ "$CHANGED" = 1 ]; then
    echo "Falha: restaurando backup $BACKUP" >&2
    cp -p "$BACKUP/admin-layout.hhvm" /opt/mk-auth/admin/hotsite_layout.hhvm
    if [ -f "$BACKUP/admin-htaccess" ]; then cp -p "$BACKUP/admin-htaccess" /opt/mk-auth/admin/.htaccess; else rm -f /opt/mk-auth/admin/.htaccess; fi
    if [ -d "$BACKUP/legacy-layouts" ]; then cp -a "$BACKUP/legacy-layouts/." "$WEBROOT/layout/"; fi
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
install -d -m 0755 "$WEBROOT/layout" "$WEBROOT/layout/vpscloud" "$WEBROOT/midias_vpscloud"
cp -a "$ROOT_DIR/theme/layout/vpscloud/." "$WEBROOT/layout/vpscloud/"
for layout in $LAYOUTS; do
  install -d -m 0755 "$WEBROOT/layout/$layout"
  cp -a "$ROOT_DIR/theme/layout/vpscloud/." "$WEBROOT/layout/$layout/"
done
cp -a "$ROOT_DIR/theme/midias_vpscloud/." "$WEBROOT/midias_vpscloud/"
find "$WEBROOT/layout/vpscloud" "$WEBROOT/layout/layout-vpscloud-whatsapp" "$WEBROOT/layout/layout-vpscloud-sistema" "$WEBROOT/midias_vpscloud" -type d -exec chmod 0755 {} \;
find "$WEBROOT/layout/vpscloud" "$WEBROOT/layout/layout-vpscloud-whatsapp" "$WEBROOT/layout/layout-vpscloud-sistema" "$WEBROOT/midias_vpscloud" -type f -exec chmod 0644 {} \;
install -m 0755 "$ROOT_DIR/installer/vpscloud-cadastro-modo" /usr/local/sbin/vpscloud-cadastro-modo
if [ -n "$SHORTCUT_THEME" ]; then
  echo "Migrando o layout exibido pelo atalho antigo para a seleção nativa: $SHORTCUT_THEME"
  "$PHP_BIN" "$ROOT_DIR/installer/configure.php" --select-theme --migrate-shortcut "$SHORTCUT_THEME"
elif [ "$PRESERVE_THEME" = 1 ]; then
  "$PHP_BIN" "$ROOT_DIR/installer/configure.php" --select-theme --preserve-theme
else
  "$PHP_BIN" "$ROOT_DIR/installer/configure.php" --select-theme
fi
SELECTED=$("$PHP_BIN" "$ROOT_DIR/installer/configure.php" --theme-name)
case "$SELECTED" in layout-vpscloud-whatsapp|layout-vpscloud-whatsapp-*) MODE=whatsapp;; *) MODE=sistema;; esac
# Remove only the shortcut installed by previous VPS Cloud releases.
# Native entrypoints and index files belonging to other themes remain untouched.
INDEX_SHORTCUT_FIXED=0
if [ -L "$WEBROOT/index.html" ]; then
  case "$(readlink "$WEBROOT/index.html")" in
    layout/layout-vpscloud-*/index.html|layout/vpscloud/index.html|"$WEBROOT"/layout/layout-vpscloud-*/index.html|"$WEBROOT"/layout/vpscloud/index.html)
      echo 'Detectado atalho index.html de uma instalação antiga do VPS CLOUD que impede a troca de tema. Corrigindo...'
      rm -f "$WEBROOT/index.html"
      INDEX_SHORTCUT_FIXED=1;;
  esac
fi
curl --noproxy '*' --connect-timeout 5 --max-time 20 -fsS "${CHECK_URL%/}/abgs-data.php?vpscloud-check=layout" -o "$BACKUP/layout-check.json"
"$PHP_BIN" "$ROOT_DIR/installer/verify-data.php" "$BACKUP/layout-check.json" "$MODE"
"$PHP_BIN" "$ROOT_DIR/installer/integrate-layout.php" "$WEBROOT" "$BACKUP"
"$PHP_BIN" -l /opt/mk-auth/admin/hotsite_layout.hhvm >/dev/null
"$PHP_BIN" "$ROOT_DIR/installer/render-meta.php" "$WEBROOT"
curl --noproxy '*' --connect-timeout 5 --max-time 20 -fsS "$CHECK_URL/" -o "$BACKUP/home-check.html"
case "$SELECTED" in
  layout-vpscloud-sistema|layout-vpscloud-whatsapp)
    grep -q 'modern-vpscloud' "$BACKUP/home-check.html" || { echo 'O hotsite instalado não foi encontrado na resposta HTTP.' >&2; exit 1; };;
  *)
    curl --noproxy '*' --connect-timeout 5 --max-time 20 -fsSL "$CHECK_URL/" -o "$BACKUP/native-check.html"
    [ -s "$BACKUP/native-check.html" ] && ! grep -Eqi 'Fatal error|Uncaught Error|Arquivo .* nao existe' "$BACKUP/native-check.html" || { echo 'O tema nativo selecionado apresenta erro; instalação revertida.' >&2; exit 1; };;
esac
# The optional admin hook must never roll back a healthy public hotsite.
if ! sh "$ROOT_DIR/installer/verify-admin.sh" "$CHECK_URL" "$BACKUP"; then
  echo 'Aviso: as opções extras de imagens não carregaram neste ambiente PHP.' >&2
  if [ -f "$BACKUP/admin-htaccess" ]; then
    cp -p "$BACKUP/admin-htaccess" /opt/mk-auth/admin/.htaccess
  else
    rm -f /opt/mk-auth/admin/.htaccess
  fi
  echo 'Configuração administrativa anterior restaurada; hotsite e dois layouts mantidos.' >&2
  if ! curl --noproxy '*' --connect-timeout 5 --max-time 20 -fsS "$CHECK_URL/admin/hotsite_layout.hhvm" -o "$BACKUP/admin-restored.html" || [ ! -s "$BACKUP/admin-restored.html" ]; then
    echo 'A página administrativa também falhou com a configuração anterior. Verifique o serviço PHP administrativo.' >&2
  fi
fi
echo "Layout selecionado: $SELECTED"
CHANGED=0
echo "Backup: $BACKUP"
echo 'Tema VPS CLOUD instalado e validado com sucesso.'
if [ "$INDEX_SHORTCUT_FIXED" = 1 ]; then
  echo 'Atalho index.html corrigido e hotsite atualizado com sucesso. A seleção de tema do MK-Auth foi restabelecida.'
fi
echo 'Escolha o cadastro em Hotsite > Layout: layout-vpscloud-whatsapp ou layout-vpscloud-sistema.'
