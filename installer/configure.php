<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || function_exists('posix_geteuid') && posix_geteuid() !== 0) {
    fwrite(STDERR, "Execute como root.\n"); exit(1);
}
$source = '/opt/mk-auth/include/conexao.php';
if (!is_file($source)) { fwrite(STDERR, "Configuração do MK-Auth não encontrada.\n"); exit(1); }
$sourceText=(string)file_get_contents($source);
function configValue(string $source,string $name): string {
    $pattern='/define\(\s*[\'\"]'.preg_quote($name,'/').'[\'\"]\s*,\s*[\'\"]([^\'\"]*)[\'\"]\s*\)/';
    if(!preg_match($pattern,$source,$match)) throw new RuntimeException('Configuração '.$name.' não encontrada.');
    return $match[1];
}
try {
    $host=configValue($sourceText,'CONHOSTNAME'); $user=configValue($sourceText,'CONUSERNAME');
    $password=configValue($sourceText,'CONPASSWRD'); $database=configValue($sourceText,'CONDATABASE');
    $LOADMYSQL=mysqli_init(); $LOADMYSQL->options(MYSQLI_OPT_CONNECT_TIMEOUT,5);
    if(!$LOADMYSQL->real_connect($host,$user,$password,$database)) throw new RuntimeException('Banco do MK-Auth indisponível.');
    $LOADMYSQL->set_charset('utf8');
} catch(Throwable $e) { fwrite(STDERR,$e->getMessage()."\n"); exit(1); }
function rows(mysqli $db, string $sql): array {
    $result=$db->query($sql); if(!$result) throw new RuntimeException($db->error);
    $items=[]; while($row=$result->fetch_assoc()) $items[]=$row; return $items;
}
try {
    if (in_array('--read-theme',$argv,true)) {
        $current=rows($LOADMYSQL,"SELECT valor FROM sis_opcao WHERE nome='layhotsite' LIMIT 1");
        echo ($current[0]['valor']??'')."\n"; $LOADMYSQL->close(); exit(0);
    }
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
            if(!$LOADMYSQL->query("INSERT INTO sis_opcao (nome,valor) VALUES ('layhotsite','vpscloud') ON DUPLICATE KEY UPDATE valor=VALUES(valor)")) throw new RuntimeException('Falha ao registrar o tema: '.$LOADMYSQL->error);
        }
    }
    $LOADMYSQL->close();
    echo "Dados do hotsite sincronizados".(!in_array('--sync-only',$argv,true)?" e tema vpscloud selecionado":"").".\n";
} catch(Throwable $e) { fwrite(STDERR,$e->getMessage()."\n"); exit(1); }
