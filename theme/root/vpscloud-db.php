<?php
// Shared by the public endpoint and installer. Never return database credentials.
function vpscloud_db() {
    if (!extension_loaded('mysqli')) throw new RuntimeException('Extensão mysqli indisponível.');
    mysqli_report(MYSQLI_REPORT_OFF);
    $source = '/opt/mk-auth/include/conexao.php';
    if (!is_readable($source)) throw new RuntimeException('Conexão do MK-Auth não encontrada.');
    ob_start();
    try { require $source; } finally { ob_end_clean(); }
    foreach (get_defined_vars() as $value) {
        if ($value instanceof mysqli && !$value->connect_errno) {
            if (!$value->set_charset('utf8mb4')) $value->set_charset('utf8');
            return $value;
        }
    }
    if (defined('CONHOSTNAME') && defined('CONUSERNAME') && defined('CONPASSWRD') && defined('CONDATABASE')) {
        $db = mysqli_init();
        $db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        if (@$db->real_connect(CONHOSTNAME, CONUSERNAME, CONPASSWRD, CONDATABASE)) {
            if (!$db->set_charset('utf8mb4')) $db->set_charset('utf8');
            return $db;
        }
    }
    throw new RuntimeException('Não foi possível conectar ao banco local do MK-Auth.');
}
function vpscloud_columns($db, $table) {
    $result = $db->query('SHOW COLUMNS FROM `'.$table.'`');
    if (!$result) throw new RuntimeException('Tabela necessária indisponível: '.$table);
    $columns = [];
    while ($row = $result->fetch_assoc()) $columns[] = $row['Field'];
    return $columns;
}
function vpscloud_projection($columns, $mapping, $required = []) {
    $fields = [];
    foreach ($mapping as $alias => $candidates) {
        $present = array_values(array_intersect($candidates, $columns));
        if (!$present && in_array($alias, $required, true)) throw new RuntimeException('Campo necessário indisponível: '.$alias);
        $parts = [];
        foreach ($present as $field) $parts[] = "NULLIF(TRIM(`".$field."`), '')";
        $fields[] = ($parts ? "COALESCE(".implode(', ', $parts).", '')" : "''").' AS `'.$alias.'`';
    }
    return implode(', ', $fields);
}
function vpscloud_visibility($columns) {
    // Never expose explicitly hidden plans as a fallback.
    return in_array('oculto', $columns, true)
        ? "LOWER(TRIM(COALESCE(`oculto`, 'nao'))) NOT IN ('sim','s','1','true')" : '1=1';
}
function vpscloud_data($db) {
    $columns = vpscloud_columns($db, 'sis_provedor');
    $projection = vpscloud_projection($columns, [
        'nome'=>['nome','razao'], 'fone'=>['fone','telefone'],
        'celular'=>['celular'], 'email'=>['email'], 'whatsapp'=>['whatsapp']
    ], ['nome']);
    $result = $db->query('SELECT '.$projection.' FROM sis_provedor LIMIT 1');
    if (!$result) throw new RuntimeException('Falha ao consultar o provedor.');
    $empresa = $result->fetch_assoc() ?: [];
    $columns = vpscloud_columns($db, 'sis_plano');
    $projection = vpscloud_projection($columns, [
        'nome'=>['nome'], 'valor'=>['valor'], 'descricao'=>['descricao']
    ], ['nome','valor']);
    $result = $db->query('SELECT '.$projection.' FROM sis_plano WHERE '.vpscloud_visibility($columns));
    if (!$result) throw new RuntimeException('Falha ao consultar os planos.');
    $planos = [];
    while ($row = $result->fetch_assoc()) {
        $price = preg_replace('/[^0-9,.-]/', '', $row['valor']);
        if (strpos($price, ',') !== false) $price = str_replace(',', '.', str_replace('.', '', $price));
        $row['valor'] = is_numeric($price) ? (float)$price : 0;
        $planos[] = $row;
    }
    usort($planos, function ($a, $b) { return $a['valor'] <=> $b['valor']; });
    return ['empresa'=>$empresa, 'planos'=>$planos];
}
