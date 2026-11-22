<?php
function vpscloud_site_url($value) {
    if (!is_string($value)) throw new RuntimeException('Informe um endereço válido.');
    $value=trim($value);
    if ($value === '') return '';
    if (!preg_match('~^[a-z][a-z0-9+.-]*://~i',$value)) $value='https://'.$value;
    $parts=parse_url($value);
    if (strlen($value)>2048 || preg_match('/[\x00-\x20\x7f\\\\]/',$value)
        || !filter_var($value,FILTER_VALIDATE_URL) || !$parts
        || !in_array(strtolower($parts['scheme'] ?? ''),['http','https'],true)
        || isset($parts['user']) || isset($parts['pass'])) {
        throw new RuntimeException('Use um endereço completo com http:// ou https://, sem usuário ou senha.');
    }
    return $value;
}
function vpscloud_site_same_host($url,$host) {
    $origin=parse_url('http://'.$host,PHP_URL_HOST);
    return $origin && strcasecmp(rtrim((string)parse_url($url,PHP_URL_HOST),'.'),rtrim($origin,'.'))===0;
}
function vpscloud_site_value($db) {
    $result=$db->query("SELECT valor FROM sis_opcao WHERE nome='vpscloud_external_site' LIMIT 1");
    if (!$result) throw new RuntimeException('Não foi possível consultar o apontamento.');
    $row=$result->fetch_assoc();
    return $row['valor'] ?? '';
}
function vpscloud_site_mode($db) {
    return vpscloud_site_value($db)===''?'local':'external';
}
function vpscloud_site_target($db) {
    $value=vpscloud_site_value($db);
    return $value==='__pending__'?'':vpscloud_site_url($value);
}
function vpscloud_site_select($db,$mode) {
    if (!in_array($mode,['local','external'],true)) throw new RuntimeException('Escolha o tipo de hotsite.');
    if ($mode==='local') vpscloud_site_store($db,'');
    elseif (vpscloud_site_value($db)==='') vpscloud_site_store($db,'__pending__');
}
function vpscloud_site_save($db,$value,$host='') {
    $value=vpscloud_site_url($value);
    if ($value!=='' && vpscloud_site_same_host($value,$host)) {
        throw new RuntimeException('Informe o domínio do site próprio, diferente do domínio deste MK-Auth.');
    }
    vpscloud_site_store($db,$value);
}
function vpscloud_site_store($db,$value) {
    $result=$db->query("SELECT valor FROM sis_opcao WHERE nome='vpscloud_external_site' LIMIT 1");
    if (!$result) throw new RuntimeException('Não foi possível consultar o apontamento.');
    $stmt=$result->fetch_assoc()
        ? $db->prepare("UPDATE sis_opcao SET valor=? WHERE nome='vpscloud_external_site'")
        : $db->prepare("INSERT INTO sis_opcao(nome,valor) VALUES('vpscloud_external_site',?)");
    if (!$stmt) throw new RuntimeException('Não foi possível preparar o apontamento.');
    $stmt->bind_param('s',$value);
    if (!$stmt->execute()) throw new RuntimeException('Não foi possível salvar o apontamento.');
}
