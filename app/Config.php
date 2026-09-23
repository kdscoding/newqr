<?php
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/newqr');

class Config
{
    private static $koneksi = null;

    public static function db()
    {
        if (self::$koneksi === null) {
            self::$koneksi = new mysqli("localhost", "root", "teratugank", "db_newqr");
        }
        return self::$koneksi;
    }

    public static function formatDateDisplay($val)
    {
        if (empty($val) || $val === '' || $val === null) return '';
        $dt = DateTime::createFromFormat('Y-m-d', $val);
        if ($dt) return $dt->format('d-M-y');
        return $val;
    }
}

function formatDateDisplay($val)
{
    return Config::formatDateDisplay($val);
}

function dedupeRows(array $rows): array
{
    $seen = [];
    $result = [];
    foreach ($rows as $r) {
        $key = (isset($r['ID']) ? $r['ID'] : '') . '|' . (isset($r['NO_URUT']) ? $r['NO_URUT'] : '');
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $result[] = $r;
    }
    return $result;
}

class Flash
{
    public static function set($type, $message)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public static function get()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}
