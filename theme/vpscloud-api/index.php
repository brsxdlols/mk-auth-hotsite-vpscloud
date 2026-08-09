<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120');
header('X-Content-Type-Options: nosniff');
$state=__DIR__.'/state.json';
if(!is_file($state)){http_response_code(503);echo '{"ok":false,"provider":{},"plans":[]}';exit;}
readfile($state);
