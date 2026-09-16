<?php
class DashboardPresenter {
    public static function render($db) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $uploadCount = mysqli_fetch_array(mysqli_query($db, "SELECT COUNT(*) as c FROM version WHERE DATE(DATE_UPLOAD)=DATE(NOW())"))['c'];
        include BASE_PATH . '/views/dashboard.php';
    }
}
