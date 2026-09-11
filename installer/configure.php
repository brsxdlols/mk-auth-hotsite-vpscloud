<?php
if (PHP_SAPI !== 'cli' || (function_exists('posix_geteuid') && posix_geteuid() !== 0)) { fwrite(STDERR, "Execute como root.\n"); exit(1); }
require __DIR__.'/../theme/root/vpscloud-db.php';
require __DIR__.'/../theme/root/vpscloud-layout.php';
try {
    if (in_array('--list-layouts', $argv, true)) { echo implode(' ', array_keys(vpscloud_layouts())); exit(0); }
    $db = vpscloud_db();
    if (in_array('--check', $argv, true)) {
        $data = vpscloud_data($db);
        echo 'MK-Auth disponível: '.count($data['planos'])." planos visíveis.\n"; exit(0);
    }
    $result = $db->query("SELECT valor FROM sis_opcao WHERE nome='layhotsite' LIMIT 1");
    if (!$result) throw new RuntimeException('Configuração de tema incompatível.');
    $row = $result->fetch_assoc();
    if (in_array('--read-theme', $argv, true)) { echo json_encode($row); exit(0); }
    if (in_array('--theme-name', $argv, true)) { echo vpscloud_selected_layout($db); exit(0); }
    $theme = vpscloud_install_layout($row['valor'] ?? '');
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
    $stmt = $row
        ? $db->prepare("UPDATE sis_opcao SET valor=? WHERE nome='layhotsite'")
        : $db->prepare("INSERT INTO sis_opcao (nome,valor) VALUES ('layhotsite',?)");
    if (!$stmt) throw new RuntimeException('Falha ao preparar seleção de tema.');
    $stmt->bind_param('s', $theme);
    if (!$stmt->execute()) throw new RuntimeException('Falha ao selecionar o tema.');
    echo "Tema atualizado.\n";
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage()."\n"); exit(1); }
