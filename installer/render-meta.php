<?php
if(PHP_SAPI!=='cli')exit;
$root=realpath($argv[1]);
require $root.'/vpscloud-meta.php';
foreach(['layout-vpscloud-sistema','layout-vpscloud-whatsapp'] as $layout){
 $path=$root.'/layout/'.$layout.'/index.html';
 $html=file_get_contents($path);
 $html=preg_replace('~<meta\b[^>]*(?:property|name)=["\x27](?:og:|twitter:)[^>]*>~i','',$html);
 $html=vpscloud_meta_html($html);
 if(file_put_contents($path,$html)===false)throw new RuntimeException('Falha ao gravar metadados.');
}
