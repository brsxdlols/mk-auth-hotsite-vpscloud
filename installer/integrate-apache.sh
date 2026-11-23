#!/bin/sh
set -eu
WEBROOT=$1
BACKUP=$2
CONF=/etc/apache2/conf-enabled/vpscloud-hotsite.conf
if ! command -v apache2ctl >/dev/null 2>&1 || [ ! -d /etc/apache2/conf-enabled ]; then
  echo 'Aviso: integração Apache indisponível; mantidas as regras do .htaccess.' >&2
  exit 0
fi
if [ -e "$CONF" ] || [ -L "$CONF" ]; then
  cp -a "$CONF" "$BACKUP/apache-site.conf"
fi
touch "$BACKUP/apache-site-changed"
rm -f "$CONF"
# HTTPS installations may ignore .htaccess (AllowOverride None).
# A managed directory rule covers both native HTTP and HTTPS virtual hosts.
cat > "$CONF" <<EOF
# Managed by MK-Auth Hotsite VPS Cloud.
<Directory "$WEBROOT">
    RewriteEngine On
    RewriteRule ^(?:index\.(?:hhvm|php|html))?$ vpscloud-site.php [END]
</Directory>
EOF
apache2ctl configtest
apache2ctl graceful
