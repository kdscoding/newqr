<?php
class PaxarPresenter {
    public static function render($db, $version) {
        $version = mysqli_real_escape_string($db, $version);
        $cek_data = mysqli_fetch_array(mysqli_query($db, "SELECT * FROM version WHERE UPLOAD_VERSION='$version'"));
        
        $zeroQty = 0;
        if ($cek_data && $cek_data["DATA"] == "paxar") {
            $zeroQty = mysqli_fetch_array(mysqli_query($db, "SELECT COUNT(*) as c FROM DATA_LABEL_SL WHERE UPLOAD_VERSION='$version' AND QTY=0"))['c'];
        }
        
        $rows = [];
        if ($cek_data && $cek_data["DATA"] == "paxar") {
            $tampil = mysqli_query($db, "SELECT * FROM DATA_LABEL_SL WHERE UPLOAD_VERSION='$version' ORDER BY NO_URUT ASC");
            while($r = mysqli_fetch_array($tampil)) {
                $rows[] = $r;
            }
        }

        $rows = dedupeRows($rows);

        $view = 'paxar.php';
        include BASE_PATH . '/views/layout_print.php';
    }
}
