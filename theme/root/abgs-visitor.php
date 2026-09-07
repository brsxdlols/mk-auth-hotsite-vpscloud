<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function visitor_ip() {
    $candidates = array(
        isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : '',
        isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : '',
        isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : '',
        isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''
    );
    foreach ($candidates as $candidate) {
        $candidate = trim($candidate);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) return $candidate;
    }
    return 'não identificado';
}

$dir = sys_get_temp_dir() . '/vpscloud-hotsite';
$file = $dir . '/visits.txt';
if (!is_dir($dir)) @mkdir($dir, 0775, true);
$count = is_file($file) ? max(0, (int)trim((string)@file_get_contents($file))) : 0;
if (empty($_COOKIE['vpscloud_visit'])) {
    $count++;
    @file_put_contents($file, (string)$count, LOCK_EX);
    setcookie('vpscloud_visit', '1', time() + 86400, '/', '', !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', true);
}
echo json_encode(array('ip' => visitor_ip(), 'count' => $count), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
