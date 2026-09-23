<?php
class AdditionalLabelPrintPresenter {
    public static function render($db, $version, $variant = 'zigzag') {
        $version = mysqli_real_escape_string($db, $version);
        $cek_data = mysqli_fetch_array(mysqli_query($db, "SELECT * FROM version WHERE UPLOAD_VERSION='$version'"));
        
        $rows = [];
        if ($cek_data && $cek_data["DATA"] == "additional_label") {
            $tampil = mysqli_query($db, "SELECT * FROM data_label_add WHERE UPLOAD_VERSION='$version' ORDER BY NO_URUT ASC");
            while($r = mysqli_fetch_array($tampil)) {
                $rows[] = $r;
            }
        }

        $rows = dedupeRows($rows);

        $view = $variant == 'left' ? 'additional_label_print_left.php' : 'additional_label_print.php';
        include BASE_PATH . '/views/' . $view;
    }
}
