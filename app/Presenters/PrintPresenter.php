<?php
class PrintPresenter {
    public static function render($db, $version, $variant = 'regular') {
        $version = mysqli_real_escape_string($db, $version);
        $cek_data = mysqli_fetch_array(mysqli_query($db, "SELECT * FROM version WHERE UPLOAD_VERSION='$version'"));
        
        $zeroQty = 0;
        if ($cek_data && $cek_data["DATA"] == "inhouse") {
            $zeroQty = mysqli_fetch_array(mysqli_query($db, "SELECT COUNT(*) as c FROM DATA_LABEL WHERE UPLOAD_VERSION='$version' AND QTY=0"))['c'];
        }
        
        $dataType = $cek_data ? $cek_data["DATA"] : '';
        
        $rows = [];
        if ($dataType == 'inhouse') {
            $tampil = mysqli_query($db, "SELECT * FROM DATA_LABEL WHERE UPLOAD_VERSION='$version' ORDER BY NO_URUT ASC");
            while($r = mysqli_fetch_array($tampil)) {
                $rows[] = $r;
            }
        } elseif ($dataType == 'supplier') {
            $tampil = mysqli_query($db, "SELECT * FROM DATA_LABEL_SP WHERE UPLOAD_VERSION='$version' ORDER BY NO_URUT ASC");
            while($r = mysqli_fetch_array($tampil)) {
                $rows[] = $r;
            }
        } elseif ($dataType == 'sbsite') {
            $tampil = mysqli_query($db, "SELECT * FROM DATA_LABEL_SBSITE WHERE UPLOAD_VERSION='$version' ORDER BY NO_URUT ASC");
            while($r = mysqli_fetch_array($tampil)) {
                $rows[] = $r;
            }
        }
        
        $view = $variant == 'xerox' ? 'print_xerox.php' : 'print.php';
        include BASE_PATH . '/views/layout_print.php';
    }
}
