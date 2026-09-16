<?php
if (isset($_POST['data'])) {
    $dt = $_POST['data'];
    $dateNow = date("Y-m-d");
    $dateNowTime = date("Y-m-d H:i:s");
    $dateOnly = date("Ymd");

    $query = mysqli_query($koneksi, "
        SELECT MAX(CAST(SUBSTRING_INDEX(UPLOAD_VERSION, '-', -1) AS UNSIGNED)) AS max_num 
        FROM version 
        WHERE UPLOAD_VERSION LIKE '$dateOnly-%'
    ");
    $row = mysqli_fetch_assoc($query);

    $lastNum = isset($row['max_num']) ? (int)$row['max_num'] : 0;
    $nextNum = $lastNum + 1;

    $UPLOAD_VERSION = $dateOnly . '-' . $nextNum;

    mysqli_query($koneksi, "INSERT INTO version (UPLOAD_VERSION, DATE_UPLOAD, DATA) VALUES ('$UPLOAD_VERSION', '$dateNowTime', '$dt')");

    $targetDir = "FILE/";
    $filename = basename($_FILES['file_excel']['name']);
    $targetFile = $targetDir . $filename;

    move_uploaded_file($_FILES['file_excel']['tmp_name'], $targetFile);

    $data = new Spreadsheet_Excel_Reader($targetFile, false);

    $baris = $data->rowcount($sheet_index = 0);
    // $cols = $data->colcount($sheet_index=0);

    // $oleError = isset($data->_ole->error) ? $data->_ole->error : 'none';
    // $workbookLen = strlen($data->data ?? '');
    // $boundsheetCount = isset($data->boundsheets) ? count($data->boundsheets) : 0;
    // $sheetsCount = isset($data->sheets) ? count($data->sheets) : 0;
    // $version = isset($data->version) ? $data->version : 'unknown';
    // $oleDebug = isset($data->_oleDebug) ? implode(' | ', $data->_oleDebug) : 'none';

    // echo '<p class="bg-info" style="padding: 15px;">Debug: File='.$targetFile.', Baris='.$baris.', Kolom='.$cols.', OLE_Error='.$oleError.', Workbook_Len='.$workbookLen.', Boundsheets='.$boundsheetCount.', Sheets='.$sheetsCount.', Version='.$version.'</p>';
    // echo '<p class="bg-info" style="padding: 5px;">OLE_Props: '.htmlspecialchars($oleDebug).'</p>';

    if ($baris == 0) {
        echo '<p class="bg-danger" style="padding: 15px;">Gagal membaca file Excel. Pastikan file berformat .xls (Excel 97-2003) dan tidak di-protect password.</p>';
        exit;
    }

    if ($dt == "additional_label") {
        for ($i = 2; $i <= $baris; $i++) {
            $NO_URUT    = trim(addslashes($data->val($i, 1)));
            $ID         = trim(addslashes($data->val($i, 2)));
            $PO         = trim(addslashes($data->val($i, 3)));
            $ITEM       = trim(addslashes($data->val($i, 4)));
            $COUNTRY    = trim(addslashes($data->val($i, 5)));
            $BUILDING   = trim(addslashes($data->val($i, 6)));
            $CELL       = trim(addslashes($data->val($i, 7)));
            $QTY        = trim(addslashes($data->val($i, 8)));
            $PRIORITY     = trim(addslashes($data->val($i, 9)));
            $PL         = trim(addslashes($data->val($i, 10)));
            $TAKEN      = trim(addslashes($data->val($i, 11)));
            $SAP        = trim(addslashes($data->val($i, 12)));

                if ($NO_URUT != '' && $NO_URUT != '0' && $ID != '') {
                    if ($PO == '') {
                        $PO = "_";
                    }
                    mysqli_query($koneksi, "INSERT INTO DATA_LABEL (NO_URUT, UPLOAD_VERSION, ID, PO, ITEM, COUNTRY, BUILDING, CELL, QTY, PRIORITY, PACKING_LIST, TAKEN_BY, SAP) 
                VALUES ('$NO_URUT', '$UPLOAD_VERSION', '$ID', '$PO', '$ITEM', '$COUNTRY', '$BUILDING', '$CELL', '$QTY', '$PRIORITY', '$PL', '$TAKEN', '$SAP')");
            }
        }
    }

    rename($targetFile, "FILE/UPLOAD/" . $UPLOAD_VERSION . "-" . $filename);

?>
    <p class="bg-success" style="padding: 15px;">UPLOAD VERSION ANDA : <?= $UPLOAD_VERSION; ?></p>
<?php
}
?>