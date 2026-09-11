<?php
if (PHP_SAPI !== 'cli') exit;
require __DIR__.'/../theme/root/vpscloud-db.php';
require __DIR__.'/../theme/root/vpscloud-layout.php';
function verify($ok, $label) { if (!$ok) throw new RuntimeException($label); echo 'PASS '.$label.PHP_EOL; }
$db=vpscloud_db();
if (!$db->query('CREATE TEMPORARY TABLE sis_opcao (nome VARCHAR(64), valor VARCHAR(255))')) throw new RuntimeException('Falha ao criar tabela temporária.');
verify(vpscloud_install_layout('')==='layout-vpscloud-sistema','instalação nova usa sistema');
verify(vpscloud_install_layout('vpscloud')==='layout-vpscloud-sistema','migração do layout antigo usa sistema');
verify(vpscloud_install_layout('layout-vpscloud-whatsapp')==='layout-vpscloud-whatsapp','atualização preserva escolha explícita');
verify(vpscloud_layout_mode($db)==='sistema','padrão sem configuração usa sistema');
vpscloud_set_layout($db,'layout-vpscloud-whatsapp');
verify(vpscloud_layout_mode($db,'sistema')==='whatsapp','layout WhatsApp prevalece sobre configuração antiga');
vpscloud_set_layout($db,'layout-vpscloud-sistema');
verify(vpscloud_layout_mode($db,'whatsapp')==='sistema','layout Sistema prevalece sobre configuração antiga');
verify(vpscloud_selected_layout($db)==='layout-vpscloud-sistema','seleção gravada no campo nativo layhotsite');
$rows=$db->query('SELECT COUNT(*) AS total FROM sis_opcao')->fetch_assoc();
verify((int)$rows['total']===1,'alternar não duplica a opção');
$failed=false;try {vpscloud_set_layout($db,'outro');}catch(RuntimeException $e){$failed=true;}
verify($failed,'layout inválido rejeitado');

foreach (vpscloud_layouts() as $theme => $mode) {
    vpscloud_set_layout($db, $theme);
    verify(vpscloud_layout_mode($db)===$mode, 'cadastro '.$theme);
    verify(vpscloud_install_layout($theme)===$theme, 'preserva '.$theme);
}
vpscloud_set_layout($db,'layout-vpscloud-sistema-internet');
verify(vpscloud_visual_mode($db)==='network','imagem fixa internet');
vpscloud_set_layout($db,'layout-vpscloud-whatsapp-licencas');
verify(vpscloud_visual_mode($db)==='license','imagem fixa licencas');
vpscloud_set_layout($db,'layout-vpscloud-sistema-dinamico');
verify(vpscloud_visual_mode($db)==='dynamic','imagens dinamicas');
