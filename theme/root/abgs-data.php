<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require '/opt/mk-auth/include/conexao.php';
$empresa = [];
$planos = [];
$config = ['cadastro_modo' => 'whatsapp'];
$configFile = '/var/www/vpscloud-config.php';
if (is_file($configFile)) {
    $loaded = require $configFile;
    if (is_array($loaded)) $config = array_merge($config, $loaded);
}
$config['cadastro_modo'] = $config['cadastro_modo'] === 'sistema' ? 'sistema' : 'whatsapp';
$result = $LOADMYSQL->query("SELECT nome,fone,celular,email,whatsapp FROM sis_provedor LIMIT 1");
if ($result) $empresa = $result->fetch_assoc() ?: [];
$result = $LOADMYSQL->query("SELECT nome,valor,descricao FROM sis_plano WHERE COALESCE(oculto,'nao') <> 'sim' ORDER BY valor+0 ASC LIMIT 18");
if ($result) while ($row = $result->fetch_assoc()) $planos[] = $row;
echo json_encode(['empresa'=>$empresa,'planos'=>$planos,'config'=>$config], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
