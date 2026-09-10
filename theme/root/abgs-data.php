<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
ini_set('display_errors', '0');
try {
    require __DIR__.'/vpscloud-db.php';
    $data = vpscloud_data(vpscloud_db());
    $config = ['cadastro_modo'=>'whatsapp'];
    if (is_file(__DIR__.'/vpscloud-config.php')) {
        $loaded = require __DIR__.'/vpscloud-config.php';
        if (is_array($loaded)) $config = array_merge($config, $loaded);
    }
    $data['config'] = ['cadastro_modo'=>$config['cadastro_modo'] === 'sistema' ? 'sistema' : 'whatsapp'];
    $data['ok'] = true;
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    error_log('VPS CLOUD dados: '.$e->getMessage());
    http_response_code(503);
    echo json_encode(['ok'=>false, 'error'=>'Não foi possível carregar os dados do provedor.'], JSON_UNESCAPED_UNICODE);
}
