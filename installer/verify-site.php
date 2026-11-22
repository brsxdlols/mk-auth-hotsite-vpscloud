<?php
if (PHP_SAPI!=='cli') exit;
require $argv[1].'/vpscloud-site-settings.php';
require $argv[1].'/vpscloud-db.php';
$target=vpscloud_site_target(vpscloud_db());
$headers=file_get_contents($argv[2]);
if ($target!=='') {
    if (!preg_match('~^HTTP/\S+ 302\b~m',$headers)
        || !preg_match('~^Location:\s*([^\r\n]+)~mi',$headers,$match)
        || trim($match[1])!==$target) {
        fwrite(STDERR,"O apontamento não corresponde ao site configurado.\n"); exit(1);
    }
    echo "Apontamento HTTP validado.\n";
} elseif (strpos(file_get_contents($argv[3]),'modern-vpscloud')===false) {
    fwrite(STDERR,"O hotsite instalado não foi encontrado na resposta HTTP.\n"); exit(1);
}
