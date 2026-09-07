<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || (function_exists('posix_geteuid') && posix_geteuid() !== 0)) { fwrite(STDERR, "Execute como root.\n"); exit(1); }
$source = '/opt/mk-auth/include/conexao.php';
if (!is_file($source)) { fwrite(STDERR, "Configuração do MK-Auth não encontrada.\n"); exit(1); }
$sourceText = (string)file_get_contents($source);
function configValue(string $source, string $name): string {
    $pattern = '/define\(\s*[\'\"]'.preg_quote($name, '/').'[\'\"]\s*,\s*[\'\"]([^\'\"]*)[\'\"]\s*\)/';
    if (!preg_match($pattern, $source, $match)) throw new RuntimeException('Configuração '.$name.' não encontrada.');
    return $match[1];
}
try {
    $db = mysqli_init(); $db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    if (!$db->real_connect(configValue($sourceText,'CONHOSTNAME'), configValue($sourceText,'CONUSERNAME'), configValue($sourceText,'CONPASSWRD'), configValue($sourceText,'CONDATABASE'))) throw new RuntimeException('Banco do MK-Auth indisponível.');
    if (in_array('--check', $argv, true)) { echo "MK-Auth disponível.\n"; exit(0); }
    $result = $db->query("SELECT valor FROM sis_opcao WHERE nome='layhotsite' LIMIT 1");
    $current = $result ? (($result->fetch_assoc()['valor'] ?? '')) : '';
    if (in_array('--read-theme', $argv, true)) { echo $current."\n"; exit(0); }
    if (in_array('--select-theme', $argv, true)) {
        $db->query('SET SESSION lock_wait_timeout=15'); $db->query('SET SESSION innodb_lock_wait_timeout=15');
        if (!$db->query("INSERT INTO sis_opcao (nome,valor) VALUES ('layhotsite','vpscloud') ON DUPLICATE KEY UPDATE valor=VALUES(valor)")) throw new RuntimeException('Falha ao selecionar o tema: '.$db->error);
        echo "Tema vpscloud selecionado.\n"; exit(0);
    }
    throw new RuntimeException('Use --check, --read-theme ou --select-theme.');
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage()."\n"); exit(1); }
