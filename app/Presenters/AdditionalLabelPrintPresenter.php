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
        $itemRowCount = [];
        $nameItemRowCount = [];
        $itemPoMap = [];
        $summaryByNameItemPo = [];

        foreach ($rows as $r) {
            $item = isset($r['ITEM']) ? trim((string)$r['ITEM']) : '';
            $nama = isset($r['TAKEN_BY']) ? trim((string)$r['TAKEN_BY']) : '';
            $po = isset($r['PO']) ? trim((string)$r['PO']) : '';
            $qty = isset($r['QTY']) ? (float)$r['QTY'] : 0;

            $itemKey = $item !== '' ? $item : '(UNKNOWN ITEM)';
            $namaKey = $nama !== '' ? $nama : '(UNKNOWN NAMA)';

            if (!isset($summaryByItem[$itemKey])) {
                $summaryByItem[$itemKey] = 0;
                $itemRowCount[$itemKey] = 0;
            }
            $summaryByItem[$itemKey] += $qty;
            $itemRowCount[$itemKey]++;

            $nameItemKey = $namaKey . '|' . $itemKey;
            if (!isset($summaryByNameItem[$nameItemKey])) {
                $summaryByNameItem[$nameItemKey] = ['nama' => $namaKey, 'item' => $itemKey, 'qty' => 0];
                $nameItemRowCount[$nameItemKey] = 0;
            }
            $summaryByNameItem[$nameItemKey]['qty'] += $qty;
            $nameItemRowCount[$nameItemKey]++;

            if ($itemKey !== '' && $po !== '') {
                if (!isset($itemPoMap[$itemKey])) {
                    $itemPoMap[$itemKey] = [];
                }
                if (!in_array($po, $itemPoMap[$itemKey], true)) {
                    $itemPoMap[$itemKey][] = $po;
                }
            }

            if ($nameItemKey !== '' && $po !== '') {
                $poKey = $namaKey . '|' . $itemKey . '|' . $po;
                if (!isset($summaryByNameItemPo[$poKey])) {
                    $summaryByNameItemPo[$poKey] = ['nama' => $namaKey, 'item' => $itemKey, 'po' => $po, 'qty' => 0];
                }
                $summaryByNameItemPo[$poKey]['qty'] += $qty;
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
