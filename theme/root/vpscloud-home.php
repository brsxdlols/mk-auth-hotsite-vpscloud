<?php
require_once __DIR__.'/vpscloud-meta.php';
$file=realpath(__DIR__.'/index.html');
if(!$file||strpos($file,realpath(__DIR__).DIRECTORY_SEPARATOR)!==0){http_response_code(503);exit;}
$html=file_get_contents($file);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');
echo strpos($html,'midias_vpscloud/')!==false?vpscloud_meta_html($html):$html;
