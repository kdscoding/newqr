<?php
require_once __DIR__ . '/../app/Config.php';
require_once __DIR__ . '/../app/Services/AuthService.php';
require_once __DIR__ . '/../app/Services/DeleteService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

AuthService::check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header("Location: /newqr/data-qr?msg=error");
    exit;
}

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    header("Location: /newqr/data-qr?msg=error");
    exit;
}

$koneksi = Config::db();

if (isset($_POST['version']) && isset($_POST['data'])) {
    DeleteService::execute($koneksi, $_POST['version'], $_POST['data']);
}

header("Location: /newqr/data-qr?msg=error");
exit;
