<?php
require_once __DIR__.'/vpscloud-site-settings.php';
header('Cache-Control: no-store, max-age=0');
try {
    require_once __DIR__.'/vpscloud-db.php';
    $target=vpscloud_site_target(vpscloud_db());
    if ($target !== '' && !vpscloud_site_same_host($target, $_SERVER['HTTP_HOST'] ?? '')) {
        header('Location: '.$target, true, 302);
        exit;
    }
} catch (Throwable $e) {
    error_log('VPS CLOUD site pointing: '.$e->getMessage());
}
// Keep the native dispatcher and theme selection when pointing is disabled.
$_SERVER['SCRIPT_FILENAME']=__DIR__.'/index.hhvm';
$_SERVER['PHP_SELF']='/index.hhvm';
require __DIR__.'/index.hhvm';
