<?php
function vpscloud_visual_modes() {
    return ['dinamico'=>'dynamic','internet'=>'network','fibra'=>'fiber','rural'=>'rural','combo'=>'combo','servicos'=>'service','cloud'=>'cloud','integracoes'=>'api','suporte'=>'support','licencas'=>'license','regularizacao'=>'regulatory'];
}
function vpscloud_layouts() {
    $layouts = ['layout-vpscloud-whatsapp'=>'whatsapp', 'layout-vpscloud-sistema'=>'sistema'];
    foreach (['sistema','whatsapp'] as $mode) {
        foreach (vpscloud_visual_modes() as $name => $visual) $layouts['layout-vpscloud-'.$mode.'-'.$name] = $mode;
    }
    return $layouts;
}
function vpscloud_visual_mode($db) {
    $selected = vpscloud_selected_layout($db);
    if (!isset(vpscloud_layouts()[$selected])) return 'dynamic';
    foreach (vpscloud_visual_modes() as $name => $visual) {
        if (substr($selected, -strlen('-'.$name)) === '-'.$name) return $visual;
    }
    return 'dynamic';
}
function vpscloud_selected_layout($db) {
    $result = $db->query("SELECT valor FROM sis_opcao WHERE nome='layhotsite' LIMIT 1");
    if (!$result) throw new RuntimeException('Não foi possível ler o layout do hotsite.');
    $row = $result->fetch_assoc();
    return $row ? $row['valor'] : '';
}
function vpscloud_layout_mode($db, $legacy = 'sistema') {
    $layouts = vpscloud_layouts();
    $selected = vpscloud_selected_layout($db);
    return $layouts[$selected] ?? ($legacy === 'whatsapp' ? 'whatsapp' : 'sistema');
}
function vpscloud_install_layout($current) {
    return isset(vpscloud_layouts()[$current]) ? $current : 'layout-vpscloud-sistema';
}
function vpscloud_set_layout($db, $theme) {
    if (!isset(vpscloud_layouts()[$theme])) throw new RuntimeException('Layout VPS CLOUD inválido.');
    $result = $db->query("SELECT valor FROM sis_opcao WHERE nome='layhotsite' LIMIT 1");
    if (!$result) throw new RuntimeException('Não foi possível consultar o layout.');
    $stmt = $result->fetch_assoc()
        ? $db->prepare("UPDATE sis_opcao SET valor=? WHERE nome='layhotsite'")
        : $db->prepare("INSERT INTO sis_opcao (nome,valor) VALUES ('layhotsite',?)");
    if (!$stmt) throw new RuntimeException('Não foi possível preparar a alteração do layout.');
    $stmt->bind_param('s', $theme);
    if (!$stmt->execute()) throw new RuntimeException('Não foi possível alterar o layout.');
}
// CLI compatibility command; HTTP requests cannot change the setting.
if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        if (!function_exists('posix_geteuid') || posix_geteuid() !== 0) throw new RuntimeException('Execute como root.');
        $mode = $argv[1] ?? '';
        if (!in_array($mode, ['whatsapp','sistema'], true)) throw new RuntimeException('Use whatsapp ou sistema.');
        $root = realpath($argv[2] ?? '/var/www');
        if (!$root || ($root !== '/var/www' && strpos($root, '/var/www/') !== 0)) throw new RuntimeException('Webroot inválido.');
        $theme = 'layout-vpscloud-'.$mode;
        if (!is_file($root.'/layout/'.$theme.'/index.html')) throw new RuntimeException('Instale primeiro os dois layouts VPS CLOUD.');
        require_once __DIR__.'/vpscloud-db.php';
        $link = $root.'/index.html';
        if (is_dir($link)) throw new RuntimeException('index.html não pode ser um diretório.');
        $temporary = $root.'/.vpscloud-index-'.bin2hex(random_bytes(6));
        if (!symlink('layout/'.$theme.'/index.html', $temporary)) throw new RuntimeException('Falha ao preparar o layout.');
        try { vpscloud_set_layout(vpscloud_db(), $theme); }
        catch (Throwable $e) { unlink($temporary); throw $e; }
        if (!rename($temporary, $link)) { unlink($temporary); throw new RuntimeException('Falha ao atualizar o arquivo inicial.'); }
        echo 'Layout selecionado: '.$theme.PHP_EOL;
    } catch (Throwable $e) { fwrite(STDERR, $e->getMessage().PHP_EOL); exit(1); }
}
