#!/bin/sh
set -eu
REPO_URL=${VPSCLOUD_REPO_URL:-https://github.com/brsxdlols/mk-auth-hotsite-vpscloud/archive/refs/heads/main.tar.gz}
TMP_DIR=$(mktemp -d /tmp/vpscloud-hotsite.XXXXXX)
trap 'rm -rf "$TMP_DIR"' EXIT HUP INT TERM
curl -fsSL "$REPO_URL" -o "$TMP_DIR/package.tar.gz"
tar -xzf "$TMP_DIR/package.tar.gz" -C "$TMP_DIR"
PACKAGE_DIR=$(find "$TMP_DIR" -mindepth 1 -maxdepth 1 -type d | head -n 1)
sh "$PACKAGE_DIR/installer/install.sh"
