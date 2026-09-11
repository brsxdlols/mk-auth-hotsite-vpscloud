<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../theme/root/vpscloud-db.php';
$LOADMYSQL=vpscloud_db();
$_SERVER['SCRIPT_FILENAME']='/opt/mk-auth/admin/hotsite_layout.hhvm';
session_id('vpscloud-integration-test');
$case=$argv[1]??'form';
if($case==='csrf'){$_SERVER['REQUEST_METHOD']='POST';$_POST=['vpscloud_visual_save'=>'1','csrf'=>'invalid','visual_mode'=>'network'];}
ob_start();
require __DIR__.'/../theme/root/vpscloud-admin-layout.php';
echo $case==='denied'?'<p>Acesso negado</p>':'<html><form><select><option>layout-vpscloud-sistema</option></select></form></html>';
ob_end_flush();
$html=ob_get_clean();
if($case==='denied'){if(strpos($html,'vpscloud-image-settings')!==false)exit(1);}
else {if(strpos($html,'vpscloud-image-settings')===false)exit(2);if($case==='csrf'&&strpos($html,'Não foi possível validar')===false)exit(3);}
echo 'PASS '.$case.PHP_EOL;
