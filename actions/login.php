<?php
require_once __DIR__ . '/../app/Config.php';
require_once __DIR__ . '/../app/Services/AuthService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = trim((string)($_POST['password'] ?? ''));

    if (AuthService::attempt($username, $password)) {
        $redirect = $_SESSION['auth_redirect'] ?? '/newqr';
        unset($_SESSION['auth_redirect']);
        header('Location: ' . $redirect);
        exit;
    }

    $_SESSION['login_error'] = 'Username atau password salah';
    header('Location: /newqr/login');
    exit;
}

$redirect = $_SESSION['auth_redirect'] ?? '/newqr';
unset($_SESSION['auth_redirect']);
header('Location: ' . $redirect);
exit;
