<?php
class DataQrPresenter {
    public static function render($db) {
        $msg = Flash::get();
        $tampil = mysqli_query($db, "SELECT * FROM version WHERE DATE(DATE_UPLOAD)=DATE(NOW()) ORDER BY DATE_UPLOAD DESC");
        $rows = [];
        while($r = mysqli_fetch_array($tampil)) {
            $rows[] = $r;
        }
        include BASE_PATH . '/views/data_qr.php';
    }
}
