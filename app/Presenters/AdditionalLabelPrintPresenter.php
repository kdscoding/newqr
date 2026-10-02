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

        $summaryByItem = [];
        $summaryByNameItem = [];
        $summaryByNameItemPo = [];
        $itemPoMap = [];

        foreach ($rows as $r) {
            $item = isset($r['ITEM']) ? trim((string)$r['ITEM']) : '';
            $nama = isset($r['TAKEN_BY']) ? trim((string)$r['TAKEN_BY']) : '';
            $po = isset($r['PO']) ? trim((string)$r['PO']) : '';
            $qty = isset($r['QTY']) ? (float)$r['QTY'] : 0;

            if ($item !== '') {
                if (!isset($summaryByItem[$item])) {
                    $summaryByItem[$item] = 0;
                }
                $summaryByItem[$item] += $qty;
            }

            if ($nama !== '' && $item !== '') {
                $key = $nama . '|' . $item;
                if (!isset($summaryByNameItem[$key])) {
                    $summaryByNameItem[$key] = ['nama' => $nama, 'item' => $item, 'qty' => 0];
                }
                $summaryByNameItem[$key]['qty'] += $qty;
            }

            if ($item !== '' && $po !== '') {
                if (!isset($itemPoMap[$item])) {
                    $itemPoMap[$item] = [];
                }
                if (!in_array($po, $itemPoMap[$item], true)) {
                    $itemPoMap[$item][] = $po;
                }
            }

            if ($nama !== '' && $item !== '' && $po !== '') {
                $key = $nama . '|' . $item . '|' . $po;
                if (!isset($summaryByNameItemPo[$key])) {
                    $summaryByNameItemPo[$key] = ['nama' => $nama, 'item' => $item, 'po' => $po, 'qty' => 0];
                }
                $summaryByNameItemPo[$key]['qty'] += $qty;
            }
        }

        $summaryByItemPo = [];
        foreach ($itemPoMap as $item => $poList) {
            $summaryByItemPo[$item] = count($poList);
        }

        $view = $variant == 'left' ? 'additional_label_print_left.php' : 'additional_label_print.php';
        include BASE_PATH . '/views/' . $view;
    }
}
