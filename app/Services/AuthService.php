<?php
class AuthService
{
    private const SESSION_LIFETIME = 28800; // 8 hours
    private const INACTIVITY_TIMEOUT = 1800; // 30 minutes

    public static function check()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['user_id'])) {
            $now = time();

            if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > self::INACTIVITY_TIMEOUT) {
                self::logout();
                $_SESSION['auth_redirect'] = $_SERVER['REQUEST_URI'];
                header('Location: /newqr/login?timeout=1');
                exit;
            }

            if (isset($_SESSION['created_at']) && ($now - $_SESSION['created_at']) > self::SESSION_LIFETIME) {
                self::logout();
                $_SESSION['auth_redirect'] = $_SERVER['REQUEST_URI'];
                header('Location: /newqr/login?expired=1');
                exit;
            }

            $_SESSION['last_activity'] = $now;
            return true;
        }

        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $route = substr($requestUri, strlen($scriptDir));
        $route = trim($route, '/');

        if ($route === 'login' || $route === 'actions/login.php' || strpos($requestUri, '/login') !== false) {
            return true;
        }

        $_SESSION['auth_redirect'] = $_SERVER['REQUEST_URI'];
        header('Location: /newqr/login');
        exit;
    }

    public static function attempt($user, $pass)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $allowed = getenv('AUTH_USER') ?: 'hwaseung';
        $password = getenv('AUTH_PASS') ?: 'hwaseung2016';

        if ($user === $allowed && $pass === $password) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $allowed;
            $_SESSION['created_at'] = time();
            $_SESSION['last_activity'] = time();
            return true;
        }

        return false;
    }

    public static function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['user_id'], $_SESSION['created_at'], $_SESSION['last_activity']);
    }
}
