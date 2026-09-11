<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../theme/root/vpscloud-db.php';
require __DIR__.'/../theme/root/vpscloud-layout.php';
$db=vpscloud_db();
$db->query('CREATE TEMPORARY TABLE sis_opcao(nome VARCHAR(64),valor VARCHAR(255))');
function verify($v,$s){if(!$v)throw new RuntimeException($s);echo 'PASS '.$s.PHP_EOL;}
verify(count(vpscloud_layouts())===2,'apenas dois layouts');
verify(vpscloud_visual_mode($db)==='dynamic','instalação nova dinâmica');
foreach(vpscloud_layouts() as $theme=>$mode){vpscloud_set_layout($db,$theme);verify(vpscloud_layout_mode($db)===$mode,'cadastro '.$mode);}
foreach(vpscloud_visual_modes() as $name=>$visual){vpscloud_set_visual_mode($db,$visual);verify(vpscloud_visual_mode($db)===$visual,'imagem '.$name);}
vpscloud_set_layout($db,'layout-vpscloud-sistema');
verify(vpscloud_visual_mode($db)==='regulatory','tema e imagem independentes');
