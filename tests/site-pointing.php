<?php
if (PHP_SAPI!=='cli')exit;
require __DIR__.'/../theme/root/vpscloud-site-settings.php';
require __DIR__.'/../theme/root/vpscloud-db.php';
function checkSite($ok,$label){if(!$ok)throw new RuntimeException($label);echo 'PASS '.$label.PHP_EOL;}
foreach(['marthconect.com.br'=>'https://marthconect.com.br','www.marthconect.com.br'=>'https://www.marthconect.com.br','https://marthconect.com.br'=>'https://marthconect.com.br','http://marthconect.com.br'=>'http://marthconect.com.br','https://example.org/path?q=one#two'=>'https://example.org/path?q=one#two'] as $input=>$expected){checkSite(vpscloud_site_url($input)===$expected,'URL '.$input);}
foreach(['javascript:alert(1)','ftp://example.org','https://user:pass@example.org',"https://example.org\r\nX-Test: bad",'https://bad host.test','//example.org',[], 'https://example.org\\@bad.test'] as $input){$failed=false;try{vpscloud_site_url($input);}catch(RuntimeException $e){$failed=true;}checkSite($failed,'endereço inválido recusado');}
checkSite(vpscloud_site_same_host('https://MK-AUTH.example.org/path','mk-auth.example.org:443'),'evita apontar para o mesmo domínio');
$db=vpscloud_db();
if(!$db->query('CREATE TEMPORARY TABLE sis_opcao(nome VARCHAR(64),valor TEXT)'))throw new RuntimeException('Falha ao isolar teste.');
checkSite(vpscloud_site_target($db)==='','padrão sem apontamento');
vpscloud_site_save($db,'marthconect.com.br','mk-auth.example.org');
checkSite(vpscloud_site_target($db)==='https://marthconect.com.br','apontamento salvo');
$failed=false;try{vpscloud_site_save($db,'https://mk-auth.example.org','mk-auth.example.org');}catch(RuntimeException $e){$failed=true;}
checkSite($failed && vpscloud_site_target($db)==='https://marthconect.com.br','loop recusado preserva preferência');
vpscloud_site_save($db,'','mk-auth.example.org');
checkSite(vpscloud_site_target($db)==='','voltar ao hotsite local');
