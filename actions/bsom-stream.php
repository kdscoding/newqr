<?php
require_once __DIR__ . '/../app/Config.php';
require_once __DIR__ . '/../app/Services/AuthService.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
AuthService::check();

$raw = isset($_GET['file']) ? (string)$_GET['file'] : '';
$raw = trim($raw);
if ($raw === '') {
    http_response_code(400);
    exit('Parameter file tidak valid.');
}

$segs = [];
foreach (explode('/', urldecode($raw)) as $seg) {
    if ($seg === '' || $seg === '.') {
        continue;
    }
    if ($seg === '..') {
        if (!empty($segs)) {
            array_pop($segs);
        }
        continue;
    }
    $segs[] = $seg;
}
$rel = implode('/', $segs);

if ($rel === '') {
    http_response_code(400);
    exit('Akses hanya diperbolehkan di dalam folder bsom.');
}

$enc = implode('/', array_map('rawurlencode', $segs));
$url = 'http://10.10.10.98/bsom/' . $enc;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HEADER => false,
]);
$data = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$err = curl_errno($ch);
curl_close($ch);

if ($err || $code !== 200 || $data === false) {
    http_response_code(502);
    exit('Gagal memuat berkas dari server bsom.');
}

$basename = basename(urldecode(end($segs)));
if ($basename === '') {
    $basename = 'file';
}

header('Content-Type: ' . ($ctype ?: 'application/octet-stream'));
header('Content-Disposition: inline; filename="' . $basename . '"; filename*=UTF-8\'\'' . rawurlencode($basename));
header('Content-Length: ' . strlen($data));
header('X-Content-Type-Options: nosniff');
echo $data;
