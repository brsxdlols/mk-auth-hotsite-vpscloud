<?php
function vpscloud_meta_db() {
    static $db=null;
    if($db===null){require_once __DIR__.'/vpscloud-db.php';$db=vpscloud_db();}
    return $db;
}
function vpscloud_meta_html($html) {
    try {
        require_once __DIR__.'/vpscloud-db.php';
        $db=vpscloud_meta_db();
        $fields=vpscloud_projection(vpscloud_columns($db,'sis_provedor'),['nome'=>['nome','razao']],['nome']);
        $result=$db->query('SELECT '.$fields.' FROM sis_provedor LIMIT 1');
        $row=$result?$result->fetch_assoc():null;
        $name=trim($row['nome']??'');
        if($name==='')return $html;
        $esc=function($s){return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');};
        $html=preg_replace('~<meta\b[^>]*(?:property|name)=["\x27](?:og:|twitter:)[^>]*>~i','',$html);
        $html=preg_replace('~<link\b[^>]*rel=["\x27](?:shortcut icon|icon)["\x27][^>]*>~i','',$html);
        $title=$esc($name);
        $host=$_SERVER['HTTP_HOST']??'';
        if(!preg_match('/^[a-z0-9.-]+(?::[0-9]+)?$/i',$host))$host='';
        $https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||($_SERVER['HTTP_X_FORWARDED_PROTO']??'')==='https';
        $base=($https?'https':'http').'://'.$host;
        $tags='<link rel="icon" type="image/png" sizes="192x192" href="/vpscloud-favicon.php"><meta property="og:type" content="website"><meta property="og:title" content="'.$title.'"><meta property="og:site_name" content="'.$title.'"><meta property="og:description" content="Você conectado sempre. Conectando você em uma experiência inesquecível!"><meta name="twitter:card" content="summary"><meta name="twitter:title" content="'.$title.'">';
        if($host!=='')$tags.='<meta property="og:url" content="'.$esc($base.'/').'"><meta property="og:image" content="'.$esc($base.'/mkfiles/logo.jpg').'">';
        $html=preg_replace_callback('~<title>.*?</title>~is',function() use ($title){return '<title>'.$title.'</title>';}, $html,1);
        return str_ireplace('</head>',$tags.'</head>',$html);
    }catch(Throwable $e){error_log('VPS CLOUD metadata: '.$e->getMessage());return $html;}
}
