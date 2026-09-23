<?php
class TlPresenter {
    public static function render($db, $version) {
        $version = mysqli_real_escape_string($db, $version);
        $cek_data = mysqli_fetch_array(mysqli_query($db, "SELECT * FROM version WHERE UPLOAD_VERSION='$version'"));
        
        $zeroQty = 0;
        if ($cek_data && $cek_data["DATA"] == "tl") {
            $zeroQty = mysqli_fetch_array(mysqli_query($db, "SELECT COUNT(*) as c FROM DATA_LABEL_TL WHERE UPLOAD_VERSION='$version' AND QTY=0"))['c'];
        }
        
        $rows = [];
        if ($cek_data && $cek_data["DATA"] == "tl") {
            $tampil = mysqli_query($db, "SELECT * FROM DATA_LABEL_TL WHERE UPLOAD_VERSION='$version' ORDER BY NO_URUT ASC");
            while($r = mysqli_fetch_array($tampil)) {
                $rows[] = $r;
            }
        }

        $rows = dedupeRows($rows);

        $view = 'trigger-label.php';
        include BASE_PATH . '/views/layout_print.php';
    }
}
