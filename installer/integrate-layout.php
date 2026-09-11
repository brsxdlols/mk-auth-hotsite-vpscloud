<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../theme/root/vpscloud-layout.php';
require __DIR__.'/../theme/root/vpscloud-db.php';
$root=realpath($argv[1]);$backup=realpath($argv[2]);
if(!$root||!$backup||strpos($backup,'/opt/mk-auth/backups/vpscloud-hotsite/')!==0)throw new RuntimeException('Destino inválido');
$admin='/opt/mk-auth/admin/hotsite_layout.hhvm';
if(!is_file($admin))throw new RuntimeException('Tela de layout não encontrada.');
$native=file_get_contents($admin);
$marker='/* VPSCLOUD_LAYOUT_SETTINGS */';
if(strpos($native,$marker)===false){
 $prefix="<?php ".$marker." require_once ".var_export($root.'/vpscloud-admin-layout.php',true)."; vpscloud_admin_layout_begin(); ?>";
 if(file_put_contents($admin,$prefix.$native)===false)throw new RuntimeException('Falha ao integrar layout.');
}
$ht=$root.'/.htaccess';$content=is_file($ht)?file_get_contents($ht):'';
$block="# BEGIN VPSCLOUD META\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteRule ^(?:index\\.html)?$ vpscloud-home.php [END]\n</IfModule>\n# END VPSCLOUD META\n";
$content=preg_replace('~# BEGIN VPSCLOUD META.*?# END VPSCLOUD META\\s*~s','',$content);
if(file_put_contents($ht,$block.$content)===false)throw new RuntimeException('Falha ao preparar metadados.');
foreach(array_merge(['vpscloud'=>null],vpscloud_legacy_layouts()) as $name=>$values){
 $path=$root.'/layout/'.$name;
 if(!file_exists($path))continue;
 if(is_link($path)||realpath(dirname($path))!==$root.'/layout')throw new RuntimeException('Layout antigo fora do diretório esperado.');
 if(!is_dir($backup.'/legacy-layouts'))mkdir($backup.'/legacy-layouts',0700);
 if(!rename($path,$backup.'/legacy-layouts/'.$name))throw new RuntimeException('Falha ao arquivar layout antigo.');
}
