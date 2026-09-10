<?php
if (PHP_SAPI !== 'cli') exit;
$data = json_decode(file_get_contents($argv[1]), true);
if (!is_array($data) || ($data['ok'] ?? false) !== true || !isset($data['empresa'], $data['planos'], $data['config']) || !is_array($data['planos'])) {
    fwrite(STDERR, "Endpoint não retornou dados válidos. Verifique o PHP do servidor web.\n"); exit(1);
}
if (isset($argv[2]) && ($data['config']['cadastro_modo'] ?? '') !== $argv[2]) {
    fwrite(STDERR, "O modo de cadastro não corresponde ao layout selecionado.\n"); exit(1);
}
echo 'HTTP validado: '.count($data['planos'])." planos visíveis.\n";
