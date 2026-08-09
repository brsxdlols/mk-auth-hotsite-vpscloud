<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || function_exists('posix_geteuid') && posix_geteuid() !== 0) {
    fwrite(STDERR, "Execute como root.\n"); exit(1);
}
$source = '/opt/mk-auth/include/conexao.php';
if (!is_file($source)) { fwrite(STDERR, "Configuração do MK-Auth não encontrada.\n"); exit(1); }
require $source;
if (!isset($LOADMYSQL) || $LOADMYSQL->connect_errno) { fwrite(STDERR, "Banco do MK-Auth indisponível.\n"); exit(1); }
function rows(mysqli $db, string $sql): array {
    $result=$db->query($sql); if(!$result) throw new RuntimeException($db->error);
    $items=[]; while($row=$result->fetch_assoc()) $items[]=$row; return $items;
}
try {
    $providers=rows($LOADMYSQL,'SELECT nome,razao,endereco,numero,bairro,cidade,estado,cep,fone,celular,whatsapp,email,site,facebook,instagram,youtube,tiktok,linkedin FROM sis_provedor LIMIT 1');
    $plans=rows($LOADMYSQL,"SELECT nome,valor,veldown,maxdown,velup,descricao,oculto FROM sis_plano WHERE COALESCE(oculto,'nao') <> 'sim' ORDER BY CAST(REPLACE(REPLACE(veldown,'M',''),'K','') AS UNSIGNED),nome LIMIT 24");
    $payload=['ok'=>true,'updated_at'=>gmdate('c'),'provider'=>$providers[0]??new stdClass(),'plans'=>$plans];
    $target='/var/www/vpscloud-api/state.json'; $tmp=$target.'.tmp-'.bin2hex(random_bytes(4));
    $json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    if($json===false||file_put_contents($tmp,$json."\n",LOCK_EX)===false||!rename($tmp,$target)) throw new RuntimeException('Falha ao atualizar o cache público.');
    chmod($target,0644); @chown($target,'root'); @chgrp($target,'www-data');
    if (!in_array('--sync-only',$argv,true)) {
        $current=rows($LOADMYSQL,"SELECT valor FROM sis_opcao WHERE nome='layhotsite' LIMIT 1");
        if (($current[0]['valor']??'')!=='vpscloud') {
            $LOADMYSQL->query('SET SESSION lock_wait_timeout=15');
            $LOADMYSQL->query('SET SESSION innodb_lock_wait_timeout=15');
            if(!$LOADMYSQL->query("UPDATE sis_opcao SET valor='vpscloud' WHERE nome='layhotsite'")) throw new RuntimeException('Falha ao selecionar o tema.');
            if($LOADMYSQL->affected_rows===0&&!$LOADMYSQL->query("INSERT INTO sis_opcao (nome,valor) SELECT 'layhotsite','vpscloud' WHERE NOT EXISTS (SELECT 1 FROM sis_opcao WHERE nome='layhotsite')")) throw new RuntimeException('Falha ao registrar o tema.');
        }
    }
    $LOADMYSQL->close();
    echo "Dados do hotsite sincronizados".(!in_array('--sync-only',$argv,true)?" e tema vpscloud selecionado":"").".\n";
} catch(Throwable $e) { fwrite(STDERR,$e->getMessage()."\n"); exit(1); }
