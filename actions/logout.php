<?php
require_once __DIR__ . '/../app/Services/AuthService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

AuthService::logout();
header("Location: /newqr/login");
exit;
