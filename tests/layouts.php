<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../theme/root/vpscloud-db.php';
require __DIR__.'/../theme/root/vpscloud-layout.php';
$db=vpscloud_db();
$db->query('CREATE TEMPORARY TABLE sis_opcao(nome VARCHAR(64),valor VARCHAR(255))');
if (vpscloud_legacy_theme_table($db)) {
    if (!$db->query('CREATE TEMPORARY TABLE sis_hotsite(layout VARCHAR(255))')) throw new RuntimeException('Falha ao isolar teste do tema nativo.');
    $db->query("INSERT INTO sis_hotsite(layout) VALUES('padrao')");
}
function verify($v,$s){if(!$v)throw new RuntimeException($s);echo 'PASS '.$s.PHP_EOL;}
verify(count(vpscloud_layouts())===2,'apenas dois layouts');
foreach(['padrao','padrao_antigo','ABGS_econ03_blue'] as $oldTheme){
    verify(vpscloud_install_layout($oldTheme)==='layout-vpscloud-whatsapp','instalador ativa WhatsApp a partir de '.$oldTheme);
}
foreach(['','original','novo_hotsite','layout-vpscloud-sistema','vpscloud','layout-vpscloud-whatsapp-fibra'] as $custom){verify(vpscloud_install_layout($custom)===$custom,'preserva tema '.$custom);}
verify(vpscloud_install_layout('layout-vpscloud-whatsapp')==='layout-vpscloud-whatsapp','atualização mantém cadastro WhatsApp');
verify(vpscloud_visual_mode($db)==='dynamic','instalação nova dinâmica');
foreach(vpscloud_layouts() as $theme=>$mode){
    vpscloud_set_layout($db,$theme);
    verify(vpscloud_layout_mode($db)===$mode,'cadastro '.$mode);
    $option=$db->query("SELECT valor FROM sis_opcao WHERE nome='layhotsite'")->fetch_assoc();
    verify($option && $option['valor']===$theme,'preferência alternativa sincronizada '.$mode);
}
foreach(vpscloud_visual_modes() as $name=>$visual){vpscloud_set_visual_mode($db,$visual);verify(vpscloud_visual_mode($db)===$visual,'imagem '.$name);}
vpscloud_set_layout($db,'layout-vpscloud-sistema');
verify(vpscloud_visual_mode($db)==='regulatory','tema e imagem independentes');
if (vpscloud_legacy_theme_table($db)) {
    if (!$db->query('DELETE FROM sis_hotsite')) throw new RuntimeException('Falha ao limpar tabela temporária.');
    verify(vpscloud_selected_layout($db)==='layout-vpscloud-sistema','tabela antiga vazia usa layhotsite');
    verify(vpscloud_layout_mode($db)==='sistema','endpoint suporta configuração antiga vazia');
    vpscloud_set_layout($db,'layout-vpscloud-whatsapp');
    verify(vpscloud_selected_layout($db)==='layout-vpscloud-whatsapp','salva preferência com tabela antiga vazia');
    verify($db->query('SELECT COUNT(*) AS total FROM sis_hotsite')->fetch_assoc()['total']==0,'não cria registro nativo artificial');
    $db->query("DELETE FROM sis_opcao WHERE nome='layhotsite'");
    verify(vpscloud_selected_layout($db)==='','sem preferência retorna vazio sem erro');
}
