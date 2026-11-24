<?php
if (PHP_SAPI !== 'cli' || (function_exists('posix_geteuid') && posix_geteuid() !== 0)) { fwrite(STDERR, "Execute como root.\n"); exit(1); }
require __DIR__.'/../theme/root/vpscloud-db.php';
require __DIR__.'/../theme/root/vpscloud-layout.php';
require __DIR__.'/../theme/root/vpscloud-site-settings.php';
try {
    if (in_array('--list-layouts', $argv, true)) { echo implode(' ', array_keys(vpscloud_layouts())); exit(0); }
    $db = vpscloud_db();
    if (in_array('--check', $argv, true)) {
        $data = vpscloud_data($db);
        echo 'MK-Auth disponível: '.count($data['planos'])." planos visíveis.\n"; exit(0);
    }
    $result = $db->query(vpscloud_legacy_theme_table($db) ? 'SELECT layout AS valor FROM sis_hotsite LIMIT 1' : "SELECT valor FROM sis_opcao WHERE nome='layhotsite' LIMIT 1");
    if (!$result) throw new RuntimeException('Configuração de tema incompatível.');
    $row = $result->fetch_assoc();
    if (!$row && vpscloud_legacy_theme_table($db)) {
        $result=$db->query("SELECT valor FROM sis_opcao WHERE nome='layhotsite' LIMIT 1");
        if (!$result) throw new RuntimeException('Não foi possível consultar a preferência de tema.');
        $row=$result->fetch_assoc();
    }
    if (in_array('--read-theme', $argv, true)) {
        $option=$db->query("SELECT valor FROM sis_opcao WHERE nome='layhotsite' LIMIT 1");
        if (!$option) throw new RuntimeException('Falha ao salvar preferência alternativa.');
        $snapshot=$row ?: ['valor'=>''];
        $snapshot['layhotsite']=$option->fetch_assoc();
        echo json_encode($snapshot); exit(0);
    }
    if (in_array('--theme-name', $argv, true)) { echo vpscloud_selected_layout($db); exit(0); }
    $previousTheme=$row['valor'] ?? '';
    // Only the native defaults and our original ABGS theme migrate automatically.
    $theme = vpscloud_install_layout($previousTheme);
    if (in_array('--select-theme',$argv,true)
        && ($theme===$previousTheme || vpscloud_site_mode($db)==='external')) {
        echo "Tema existente preservado.\n";
        exit(0);
    }
    if (in_array('--restore-theme', $argv, true)) {
        $previous = json_decode(file_get_contents($argv[count($argv)-1]), true);
        if (!$previous) {
            if (!$db->query("DELETE FROM sis_opcao WHERE nome='layhotsite'")) throw new RuntimeException('Falha ao restaurar tema.');
            exit(0);
        }
        $theme = $previous['valor'];
    } elseif (!in_array('--select-theme', $argv, true)) throw new RuntimeException('Opção inválida.');
    $db->query('SET SESSION lock_wait_timeout=15');
    $db->query('SET SESSION innodb_lock_wait_timeout=15');
    vpscloud_write_native_layout($db,$theme);
    if (isset($previous) && array_key_exists('layhotsite',$previous)) {
        if ($previous['layhotsite'] === null) {
            if (!$db->query("DELETE FROM sis_opcao WHERE nome='layhotsite'")) throw new RuntimeException('Falha ao restaurar preferência alternativa.');
        } else {
            $stmt=$db->prepare("UPDATE sis_opcao SET valor=? WHERE nome='layhotsite'");
            if (!$stmt) throw new RuntimeException('Falha ao preparar restauração.');
            $value=$previous['layhotsite']['valor'];
            $stmt->bind_param('s',$value);
            if (!$stmt->execute()) throw new RuntimeException('Falha ao restaurar preferência alternativa.');
        }
        vpscloud_clear_layout_cache();
    }
    echo "Tema atualizado.\n";
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage()."\n"); exit(1); }
