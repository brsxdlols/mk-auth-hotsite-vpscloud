<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../theme/root/vpscloud-db.php';
$LOADMYSQL=vpscloud_db();
$LOADMYSQL->query('CREATE TEMPORARY TABLE sis_opcao(nome VARCHAR(64),valor TEXT)');
$_SERVER['SCRIPT_FILENAME']='/opt/mk-auth/admin/hotsite_layout.hhvm';
session_id('vpscloud-integration-test');
$case=$argv[1]??'form';
if($case==='csrf'){$_SERVER['REQUEST_METHOD']='POST';$_POST=['vpscloud_visual_save'=>'1','csrf'=>'invalid','visual_mode'=>'network'];}
if(in_array($case,['site','site-invalid','site-csrf'],true)){
 $_SERVER['REQUEST_METHOD']='POST';$_SERVER['HTTP_HOST']='mk-auth.example.org';
 $_POST=['vpscloud_site_save'=>'1','csrf'=>$case==='site-csrf'?'invalid':hash('sha256','vpscloud-visual|'.session_id()),'site_mode'=>'external','site_url'=>$case==='site-invalid'?'javascript:alert(1)':'marthconect.com.br'];
}
ob_start();
require __DIR__.'/../theme/root/vpscloud-admin-layout.php';
echo $case==='denied'?'<p>Acesso negado</p>':'<html><form><select><option>layout-vpscloud-sistema</option></select></form></html>';
ob_end_flush();
$html=ob_get_clean();
if($case==='denied'){if(strpos($html,'vpscloud-image-settings')!==false||strpos($html,'vpscloud-site-settings')!==false)exit(1);}
else {if(strpos($html,'vpscloud-image-settings')===false||strpos($html,'vpscloud-site-settings')===false)exit(2);if(in_array($case,['csrf','site-csrf'],true)&&strpos($html,'Não foi possível validar')===false)exit(3);}
if($case!=='denied'){
 if(strpos($html,'data-vpscloud-theme')===false || strpos($html,'value="__vpscloud_pointing__"')===false)exit(5);
 if($case==='form' && !preg_match('~id="vpscloud-site-settings"[^>]* hidden~',$html))exit(6);
 if($case==='site' && strpos($html,'value="__vpscloud_pointing__" selected')===false)exit(7);
}
if(in_array($case,['site','site-invalid','site-csrf'],true)){
 $expected=$case==='site'?'https://marthconect.com.br':'';
 if(vpscloud_site_target($LOADMYSQL)!==$expected)exit(4);
}
echo 'PASS '.$case.PHP_EOL;
