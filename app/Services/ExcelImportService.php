<?php
class ExcelImportService
{
    private static function excelRead($data, $row, $col)
    {
        $val = $data->val($row, $col);
        return is_null($val) ? '' : (string)$val;
    }

    private static function excelReadInt($data, $row, $col)
    {
        $val = $data->val($row, $col);
        if (is_null($val)) return '';
        if (is_string($val) && $val === '') return '';
        return (string)(int)$val;
    }

    private static function parseExcelDate($val)
    {
        if (empty($val) || $val === '' || $val === null) return '';
        $val = trim($val);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) return $val;
        $formats = [
            'd/m/y', 'd/m/Y', 'd/M/y', 'd/M/Y',
            'j/M/y', 'j/M/Y', 'd-M-y', 'd-M-Y',
            'j-M-y', 'j-M-Y',
        ];
        foreach ($formats as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $val);
            if ($dt) return $dt->format('Y-m-d');
        }
        if (preg_match('/^(\d{1,2})[-\/]([A-Za-z]{3,9})$/', $val, $m)) {
            $day = $m[1];
            $mon = $m[2];
            $year = date('y');
            $dt = DateTime::createFromFormat('d-M-y', $day . '-' . $mon . '-' . $year);
            if ($dt) return $dt->format('Y-m-d');
        }
        return '';
    }

    public static function process($db, $dataType, $file)
    {
        $dateNow = date("Y-m-d");
        $dateNowTime = date("Y-m-d H:i:s");
        $dateOnly = date("Ymd");

        $stmt = $db->prepare("
            SELECT MAX(CAST(SUBSTRING_INDEX(UPLOAD_VERSION, '-', -1) AS UNSIGNED)) AS max_num 
            FROM version 
            WHERE UPLOAD_VERSION LIKE ?
        ");
        $like = $dateOnly . '-%';
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $lastNum = isset($row['max_num']) ? (int)$row['max_num'] : 0;
        $nextNum = $lastNum + 1;
        $UPLOAD_VERSION = $dateOnly . '-' . $nextNum;

        $targetDir = BASE_PATH . "/FILE/";
        $originalName = basename($file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($ext !== 'xls') {
            unset($data);
            @unlink($file['tmp_name']);
            return [
                'success' => false,
                'errors' => [
                    [
                        'row' => '-', 'col' => '-', 'field' => 'FILE',
                        'value' => $originalName,
                        'type' => 'invalid_file',
                        'message' => "Format file tidak didukung. Hanya file .xls yang diperbolehkan.",
                        'suggestion' => "Upload file Excel dengan format .xls sesuai template."
                    ]
                ],
                'dataType' => $dataType
            ];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowedMimes = [
            'application/vnd.ms-excel',
            'application/octet-stream',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];

        if (!in_array($mime, $allowedMimes, true)) {
            unset($data);
            @unlink($file['tmp_name']);
            return [
                'success' => false,
                'errors' => [
                    [
                        'row' => '-', 'col' => '-', 'field' => 'FILE',
                        'value' => $mime,
                        'type' => 'invalid_mime',
                        'message' => "Tipe file tidak diizinkan.",
                        'suggestion' => "Pastikan file adalah Excel .xls yang valid."
                    ]
                ],
                'dataType' => $dataType
            ];
        }

        $safeName = uniqid('upload_', true) . '.xls';
        $targetFile = $targetDir . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            return [
                'success' => false,
                'errors' => [
                    [
                        'row' => '-', 'col' => '-', 'field' => 'FILE',
                        'value' => $file['tmp_name'],
                        'type' => 'upload_failed',
                        'message' => "Gagal memindahkan file upload.",
                        'suggestion' => "Periksa permission folder /FILE/."
                    ]
                ],
                'dataType' => $dataType
            ];
        }

        $data = new Spreadsheet_Excel_Reader($targetFile, false);
        $baris = $data->rowcount($sheet_index = 0);
        $cols = $data->colcount($sheet_index = 0);

        $expectedFormats = [
            'inhouse' => [
                'cols' => 12,
                'headers' => ['NO_URUT', 'ID', 'PO', 'ITEM', 'COUNTRY', 'BUILDING', 'CELL', 'START', 'SDD', 'QTY', 'CODE', 'INTERNAL SAP'],
                'numeric' => [1, 10],
                'date' => [8, 9],
                'required' => [1, 2, 4, 10],
            ],
            'sbsite' => [
                'cols' => 11,
                'headers' => ['NO_URUT', 'ID_SB_SITE', 'PO_10', 'ITEM', 'COUNTRY', 'BUILDING', 'CELL', 'SDD', 'QTY', 'PACKING_LIST', 'INTERNAL SAP'],
                'numeric' => [1, 9],
                'date' => [8],
                'required' => [1, 2, 4, 9],
            ],
            'paxar' => [
                'cols' => 13,
                'headers' => ['NO_URUT', 'ID', 'ITEM', 'PO', 'PRINT_DATE', 'PRINTED_BY', 'REMARKS', 'CUST', 'COUNTRY', 'ART', 'MODEL_NAME', 'QTY', 'CELL'],
                'numeric' => [1, 12],
                'date' => [5],
                'required' => [1, 2, 4, 12],
            ],
            'supplier' => [
                'cols' => 10,
                'headers' => ['NO_URUT', 'ID', 'PO_10', 'ITEM', 'COUNTRY', 'BUILDING', 'CELL', 'REMARK', 'REMARK2', 'REMARK3'],
                'numeric' => [1],
                'date' => [],
                'required' => [1, 2, 4],
            ],
            'tl' => [
                'cols' => 12,
                'headers' => ['NO_URUT', 'ID', 'PO', 'ITEM', 'COUNTRY', 'BUILDING', 'CELL', 'START', 'SDD', 'QTY', 'CODE', 'INTERNAL SAP'],
                'numeric' => [1, 10],
                'date' => [8, 9],
                'required' => [1, 2, 4, 10],
            ],
            'additional_label' => [
                'cols' => 12,
                'headers' => ['NO_URUT', 'ID', 'PO', 'ITEM', 'COUNTRY', 'BUILDING', 'CELL', 'QTY', 'PRIORITY', 'PACKING_LIST', 'TAKEN_BY', 'INTERNAL SAP'],
                'numeric' => [1, 8],
                'date' => [],
                'required' => [1, 2, 4, 8],
            ],
        ];

        $validationErrors = [];
        if (isset($expectedFormats[$dataType])) {
            $fmt = $expectedFormats[$dataType];
            if ($cols != $fmt['cols']) {
                $validationErrors[] = [
                    'row' => '-',
                    'col' => '-',
                    'field' => 'Total Kolom',
                    'value' => $cols . ' kolom',
                    'type' => 'column_count',
                    'message' => "Jumlah kolom tidak sesuai. Ditemukan: $cols, Diharapkan: {$fmt['cols']} untuk tipe '$dataType'",
                    'suggestion' => "Pastikan file Excel memiliki exactly {$fmt['cols']} kolom. Kolom yang ada: $cols, Kolom yang dibutuhkan: {$fmt['cols']}. Periksa kembali template."
                ];
            } else {
                for ($c = 1; $c <= $cols; $c++) {
                    $rawHeader = $data->val(1, $c);
                    $header = strtoupper(trim((string)$rawHeader));
                    $expected = strtoupper($fmt['headers'][$c - 1]);
                    if ($header !== $expected) {
                        $type = gettype($rawHeader);
                        $validationErrors[] = [
                            'row' => 1,
                            'col' => $c,
                            'field' => $fmt['headers'][$c - 1],
                            'value' => (string)$rawHeader,
                            'type' => 'header_mismatch',
                            'message' => "Kolom $c: Header '" . htmlspecialchars((string)$rawHeader) . "' (tipe: $type) tidak sesuai",
                            'expected' => $fmt['headers'][$c - 1],
                            'found' => (string)$rawHeader,
                            'suggestion' => "Ubah header kolom $c menjadi '{$fmt['headers'][$c - 1]}' persis seperti di template."
                        ];
                    }
                }
            }
        }

        if (!empty($validationErrors)) {
            unset($data);
            @unlink($targetFile);
            return [
                'success' => false,
                'errors' => $validationErrors,
                'dataType' => $dataType
            ];
        }

        $importedCount = 0;
        $skippedCount = 0;
        $rowErrors = [];
        $queryErrors = [];

        if ($dataType == "inhouse") {
            $stmt_inhouse = $db->prepare("INSERT IGNORE INTO DATA_LABEL 
                (NO_URUT, UPLOAD_VERSION, ID, PO, ITEM, COUNTRY, BUILDING, CELL, START, SDD, QTY, REMARK, SAP) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            for ($i = 2; $i <= $baris; $i++) {
                $NO_URUT = self::excelReadInt($data, $i, 1);
                $ID      = self::excelRead($data, $i, 2);

                if (trim($ID) === '') {
                    $skippedCount++;
                    continue;
                }

                $PO      = self::excelRead($data, $i, 3);
                $ITEM    = self::excelRead($data, $i, 4);
                $COUNTRY = self::excelRead($data, $i, 5);
                $BUILDING = self::excelRead($data, $i, 6);
                $CELL    = substr(self::excelRead($data, $i, 7), 0, 9);
                $START   = self::parseExcelDate(self::excelRead($data, $i, 8));
                $SDD     = self::parseExcelDate(self::excelRead($data, $i, 9));
                $QTY     = self::excelReadInt($data, $i, 10);
                $REMARK  = self::excelRead($data, $i, 11);
                $SAP     = self::excelRead($data, $i, 12);

                if ($NO_URUT == '' || $NO_URUT == '0') {
                    $rowErrors[] = [
                        'row' => $i, 'col' => 1, 'field' => 'NO_URUT', 'value' => $NO_URUT,
                        'type' => 'required_empty', 'message' => "Baris $i: Kolom 'NO_URUT' kosong atau nol",
                        'suggestion' => "Isi kolom NO_URUT dengan angka urut yang valid."
                    ];
                }
                if ($QTY == '' || $QTY == '0') {
                    $rowErrors[] = [
                        'row' => $i, 'col' => 10, 'field' => 'QTY', 'value' => $QTY,
                        'type' => 'invalid_numeric', 'message' => "Baris $i: Kolom 'QTY' harus angka dan tidak boleh 0",
                        'suggestion' => "Isi kolom QTY dengan angka lebih dari 0."
                    ];
                }
                if ($START == '' || $SDD == '') {
                    $rowErrors[] = [
                        'row' => $i, 'col' => ($START == '' ? 8 : 9), 'field' => 'START/SDD',
                        'value' => 'START=' . $START . ', SDD=' . $SDD,
                        'type' => 'invalid_date', 'message' => "Baris $i: Format tanggal tidak valid",
                        'suggestion' => "Gunakan format tanggal DD/MM/YYYY atau DD-MM-YYYY. Contoh: 25/12/2024"
                    ];
                }

                if ($NO_URUT != '' && $NO_URUT != '0') {
                    if ($PO == '') $ID = "_";
                    $stmt_inhouse->bind_param('issssssssssss', $NO_URUT, $UPLOAD_VERSION, $ID, $PO, $ITEM, $COUNTRY, $BUILDING, $CELL, $START, $SDD, $QTY, $REMARK, $SAP);
                    $result = $stmt_inhouse->execute();
                    if (!$result) {
                        $queryErrors[] = [
                            'row' => $i, 'col' => '-', 'field' => 'DATABASE',
                            'value' => $stmt_inhouse->error, 'type' => 'query_error',
                            'message' => "Baris $i: Gagal insert ke database",
                            'suggestion' => "Periksa koneksi database atau struktur tabel DATA_LABEL."
                        ];
                    } elseif ($stmt_inhouse->affected_rows > 0) {
                        $importedCount++;
                    }
                }
            }
            $stmt_inhouse->close();
        } elseif ($dataType == "supplier") {
            $stmt_supplier = $db->prepare("INSERT IGNORE INTO DATA_LABEL_SP 
                (NO_URUT, UPLOAD_VERSION, ID, PO, ITEM, COUNTRY, BUILDING, CELL, REMARK, REMARK2, REMARK3) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            for ($i = 2; $i <= $baris; $i++) {
                $NO_URUT = self::excelReadInt($data, $i, 1);
                $ID      = self::excelRead($data, $i, 2);

                if (trim($ID) === '') {
                    $skippedCount++;
                    continue;
                }

                $PO      = self::excelRead($data, $i, 3);
                $ITEM    = self::excelRead($data, $i, 4);
                $COUNTRY = self::excelRead($data, $i, 5);
                $BUILDING = self::excelRead($data, $i, 6);
                $CELL    = substr(self::excelRead($data, $i, 7), 0, 9);
                $REMARK  = self::excelRead($data, $i, 8);
                $REMARK2 = self::excelRead($data, $i, 9);
                $REMARK3 = self::excelRead($data, $i, 10);

                if ($NO_URUT == '' || $NO_URUT == '0') {
                    $rowErrors[] = ['row' => $i, 'col' => 1, 'field' => 'NO_URUT', 'value' => $NO_URUT, 'type' => 'required_empty', 'message' => "Baris $i: Kolom 'NO_URUT' kosong", 'suggestion' => "Isi dengan angka urut yang valid."];
                }

                if ($NO_URUT != '' && $NO_URUT != '0') {
                    if ($PO == '') $ID = "_";
                    $stmt_supplier->bind_param('issssssssss', $NO_URUT, $UPLOAD_VERSION, $ID, $PO, $ITEM, $COUNTRY, $BUILDING, $CELL, $REMARK, $REMARK2, $REMARK3);
                    $result = $stmt_supplier->execute();
                    if (!$result) {
                        $queryErrors[] = [
                            'row' => $i, 'col' => '-', 'field' => 'DATABASE',
                            'value' => $stmt_supplier->error, 'type' => 'query_error',
                            'message' => "Baris $i: Gagal insert ke database",
                            'suggestion' => "Periksa koneksi database atau struktur tabel DATA_LABEL_SP."
                        ];
                    } else {
                        $importedCount++;
                    }
                }
            }
            $stmt_supplier->close();
        } elseif ($dataType == "sbsite") {
            $stmt_sbsite = $db->prepare("INSERT IGNORE INTO DATA_LABEL_SBSITE 
                (NO_URUT, UPLOAD_VERSION, ID, PO, ITEM, COUNTRY, BUILDING, CELL, SDD, QTY, PACKING_LIST, SAP, ID_SAP) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            for ($i = 2; $i <= $baris; $i++) {
                $NO_URUT    = self::excelReadInt($data, $i, 1);
                $ID         = self::excelRead($data, $i, 2);

                if (trim($ID) === '') {
                    $skippedCount++;
                    continue;
                }

                $PO         = self::excelRead($data, $i, 3);
                $ITEM       = self::excelRead($data, $i, 4);
                $COUNTRY    = self::excelRead($data, $i, 5);
                $BUILDING   = self::excelRead($data, $i, 6);
                $CELL       = substr(self::excelRead($data, $i, 7), 0, 9);
                $SDD        = self::parseExcelDate(self::excelRead($data, $i, 8));
                $QTY        = self::excelReadInt($data, $i, 9);
                $PACKING_LIST = self::excelRead($data, $i, 10);
                $SAP        = self::excelRead($data, $i, 11);
                $ID_SAP     = self::excelRead($data, $i, 12);

                if ($NO_URUT == '' || $NO_URUT == '0') {
                    $rowErrors[] = ['row' => $i, 'col' => 1, 'field' => 'NO_URUT', 'value' => $NO_URUT, 'type' => 'required_empty', 'message' => "Baris $i: Kolom 'NO_URUT' kosong", 'suggestion' => "Isi dengan angka urut yang valid."];
                }
                if ($QTY == '' || $QTY == '0') {
                    $rowErrors[] = ['row' => $i, 'col' => 9, 'field' => 'QTY', 'value' => $QTY, 'type' => 'invalid_numeric', 'message' => "Baris $i: Kolom 'QTY' harus angka dan tidak boleh 0", 'suggestion' => "Isi dengan angka lebih dari 0."];
                }
                if ($SDD == '') {
                    $rowErrors[] = ['row' => $i, 'col' => 8, 'field' => 'SDD', 'value' => $SDD, 'type' => 'invalid_date', 'message' => "Baris $i: Format tanggal SDD tidak valid", 'suggestion' => "Gunakan format DD/MM/YYYY atau DD-MM/YYYY."];
                }

                if ($NO_URUT != '' && $NO_URUT != '0') {
                    if ($PO == '') $ID = "_";
                    $stmt_sbsite->bind_param('issssssssssss', $NO_URUT, $UPLOAD_VERSION, $ID, $PO, $ITEM, $COUNTRY, $BUILDING, $CELL, $SDD, $QTY, $PACKING_LIST, $SAP, $ID_SAP);
                    $result = $stmt_sbsite->execute();
                    if (!$result) {
                        $queryErrors[] = [
                            'row' => $i, 'col' => '-', 'field' => 'DATABASE',
                            'value' => $stmt_sbsite->error, 'type' => 'query_error',
                            'message' => "Baris $i: Gagal insert ke database",
                            'suggestion' => "Periksa koneksi database atau struktur tabel DATA_LABEL_SBSITE."
                        ];
                    } else {
                        $importedCount++;
                    }
                }
            }
            $stmt_sbsite->close();
        } elseif ($dataType == "paxar") {
            $stmt_paxar = $db->prepare("INSERT IGNORE INTO DATA_LABEL_SL 
                (NO_URUT, UPLOAD_VERSION, ID, ITEM, PO, PRINT_DATE, PRINTED_BY, REMARKS, CUST, COUNTRY, ART, MODEL_NAME, QTY, CELL) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            for ($i = 2; $i <= $baris; $i++) {
                $NO_URUT    = self::excelReadInt($data, $i, 1);
                $ID         = self::excelRead($data, $i, 2);

                if (trim($ID) === '') {
                    $skippedCount++;
                    continue;
                }

                $ITEM       = self::excelRead($data, $i, 3);
                $PO         = self::excelRead($data, $i, 4);
                $PRINT_DATE = self::parseExcelDate(self::excelRead($data, $i, 5));
                $PRINTED_BY = self::excelRead($data, $i, 6);
                $REMARKS    = substr(self::excelRead($data, $i, 7), 0, 19);
                $CUST       = self::excelRead($data, $i, 8);
                $COUNTRY    = substr(self::excelRead($data, $i, 9), 0, 10);
                $ART        = self::excelRead($data, $i, 10);
                $MODEL_NAME = substr(self::excelRead($data, $i, 11), 0, 19);
                $QTY        = self::excelReadInt($data, $i, 12);
                $CELL       = substr(self::excelRead($data, $i, 13), 0, 3);

                if ($NO_URUT == '' || $NO_URUT == '0') {
                    $rowErrors[] = ['row' => $i, 'col' => 1, 'field' => 'NO_URUT', 'value' => $NO_URUT, 'type' => 'required_empty', 'message' => "Baris $i: Kolom 'NO_URUT' kosong", 'suggestion' => "Isi dengan angka urut yang valid."];
                }
                if ($QTY == '' || $QTY == '0') {
                    $rowErrors[] = ['row' => $i, 'col' => 12, 'field' => 'QTY', 'value' => $QTY, 'type' => 'invalid_numeric', 'message' => "Baris $i: Kolom 'QTY' harus angka dan tidak boleh 0", 'suggestion' => "Isi dengan angka lebih dari 0."];
                }
                if ($PRINT_DATE == '') {
                    $rowErrors[] = ['row' => $i, 'col' => 5, 'field' => 'PRINT_DATE', 'value' => self::excelRead($data, $i, 5), 'type' => 'invalid_date', 'message' => "Baris $i: Format tanggal PRINT_DATE tidak valid", 'suggestion' => "Gunakan format DD/MM/YYYY atau DD-MM/YYYY."];
                }
                if (strlen($COUNTRY) > 10) {
                    $rowErrors[] = ['row' => $i, 'col' => 9, 'field' => 'COUNTRY', 'value' => $COUNTRY, 'type' => 'max_length_exceeded', 'message' => "Baris $i: Kolom 'COUNTRY' maksimal 10 karakter", 'suggestion' => "Persingkat teks COUNTRY menjadi maksimal 10 karakter."];
                }

                if ($NO_URUT != '' && $NO_URUT != '0') {
                    if ($PO == '') $ID = "_";
                    $stmt_paxar->bind_param('isssssssssssss', $NO_URUT, $UPLOAD_VERSION, $ID, $ITEM, $PO, $PRINT_DATE, $PRINTED_BY, $REMARKS, $CUST, $COUNTRY, $ART, $MODEL_NAME, $QTY, $CELL);
                    $result = $stmt_paxar->execute();
                    if (!$result) {
                        $queryErrors[] = [
                            'row' => $i, 'col' => '-', 'field' => 'DATABASE',
                            'value' => $stmt_paxar->error, 'type' => 'query_error',
                            'message' => "Baris $i: Gagal insert ke database",
                            'suggestion' => "Periksa koneksi database atau struktur tabel DATA_LABEL_SL."
                        ];
                    } else {
                        $importedCount++;
                    }
                }
            }
            $stmt_paxar->close();
        } elseif ($dataType == "tl") {
            $stmt_tl = $db->prepare("INSERT IGNORE INTO DATA_LABEL_TL 
                (NO_URUT, UPLOAD_VERSION, ID, PO, ITEM, COUNTRY, BUILDING, CELL, START, SDD, QTY, REMARK, SAP) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            for ($i = 2; $i <= $baris; $i++) {
                $NO_URUT = self::excelReadInt($data, $i, 1);
                $ID      = self::excelRead($data, $i, 2);

                if (trim($ID) === '') {
                    $skippedCount++;
                    continue;
                }

                $PO      = self::excelRead($data, $i, 3);
                $ITEM    = self::excelRead($data, $i, 4);
                $COUNTRY = self::excelRead($data, $i, 5);
                $BUILDING = self::excelRead($data, $i, 6);
                $CELL    = substr(self::excelRead($data, $i, 7), 0, 9);
                $START   = self::parseExcelDate(self::excelRead($data, $i, 8));
                $SDD     = self::parseExcelDate(self::excelRead($data, $i, 9));
                $QTY     = self::excelReadInt($data, $i, 10);
                $REMARK  = self::excelRead($data, $i, 11);
                $SAP     = self::excelRead($data, $i, 12);

                if ($NO_URUT == '' || $NO_URUT == '0') {
                    $rowErrors[] = ['row' => $i, 'col' => 1, 'field' => 'NO_URUT', 'value' => $NO_URUT, 'type' => 'required_empty', 'message' => "Baris $i: Kolom 'NO_URUT' kosong", 'suggestion' => "Isi dengan angka urut yang valid."];
                }
                if ($QTY == '' || $QTY == '0') {
                    $rowErrors[] = ['row' => $i, 'col' => 10, 'field' => 'QTY', 'value' => $QTY, 'type' => 'invalid_numeric', 'message' => "Baris $i: Kolom 'QTY' harus angka dan tidak boleh 0", 'suggestion' => "Isi dengan angka lebih dari 0."];
                }
                if ($START == '' || $SDD == '') {
                    $rowErrors[] = ['row' => $i, 'col' => ($START == '' ? 8 : 9), 'field' => 'START/SDD', 'value' => 'START=' . $START . ', SDD=' . $SDD, 'type' => 'invalid_date', 'message' => "Baris $i: Format tanggal tidak valid", 'suggestion' => "Gunakan format DD/MM/YYYY atau DD-MM-YYYY."];
                }

                if ($NO_URUT != '' && $NO_URUT != '0') {
                    $stmt_tl->bind_param('issssssssssss', $NO_URUT, $UPLOAD_VERSION, $ID, $PO, $ITEM, $COUNTRY, $BUILDING, $CELL, $START, $SDD, $QTY, $REMARK, $SAP);
                    $result = $stmt_tl->execute();
                    if (!$result) {
                        $queryErrors[] = [
                            'row' => $i, 'col' => '-', 'field' => 'DATABASE',
                            'value' => $stmt_tl->error, 'type' => 'query_error',
                            'message' => "Baris $i: Gagal insert ke database",
                            'suggestion' => "Periksa koneksi database atau struktur tabel DATA_LABEL_TL."
                        ];
                    } else {
                        $importedCount++;
                    }
                }
            }
            $stmt_tl->close();
        } elseif ($dataType == "additional_label") {
            $stmt_add = $db->prepare("INSERT IGNORE INTO data_label_add 
                (NO_URUT, UPLOAD_VERSION, ID, PO, ITEM, COUNTRY, BUILDING, CELL, QTY, PRIORITY, PACKING_LIST, TAKEN_BY, SAP) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            for ($i = 2; $i <= $baris; $i++) {
                $NO_URUT    = self::excelReadInt($data, $i, 1);
                $ID         = self::excelRead($data, $i, 2);

                if (trim($ID) === '') {
                    $skippedCount++;
                    continue;
                }

                $PO         = self::excelRead($data, $i, 3);
                $ITEM       = self::excelRead($data, $i, 4);
                $COUNTRY    = self::excelRead($data, $i, 5);
                $BUILDING   = self::excelRead($data, $i, 6);
                $CELL       = self::excelRead($data, $i, 7);
                $QTY        = self::excelReadInt($data, $i, 8);
                $PRIORITY   = self::excelRead($data, $i, 9);
                $PACKING_LIST = self::excelRead($data, $i, 10);
                $TAKEN_BY   = self::excelRead($data, $i, 11);
                $SAP        = self::excelRead($data, $i, 12);

                if ($NO_URUT == '' || $NO_URUT == '0') {
                    $rowErrors[] = ['row' => $i, 'col' => 1, 'field' => 'NO_URUT', 'value' => $NO_URUT, 'type' => 'required_empty', 'message' => "Baris $i: Kolom 'NO_URUT' kosong", 'suggestion' => "Isi dengan angka urut yang valid."];
                }
                if ($QTY == '' || $QTY == '0') {
                    $rowErrors[] = ['row' => $i, 'col' => 8, 'field' => 'QTY', 'value' => $QTY, 'type' => 'invalid_numeric', 'message' => "Baris $i: Kolom 'QTY' harus angka dan tidak boleh 0", 'suggestion' => "Isi dengan angka lebih dari 0."];
                }

                if ($NO_URUT != '' && $NO_URUT != '0') {
                    if ($PO == '') $PO = "_";
                    $stmt_add->bind_param('issssssssssss', $NO_URUT, $UPLOAD_VERSION, $ID, $PO, $ITEM, $COUNTRY, $BUILDING, $CELL, $QTY, $PRIORITY, $PACKING_LIST, $TAKEN_BY, $SAP);
                    $result = $stmt_add->execute();
                    if (!$result) {
                        $queryErrors[] = [
                            'row' => $i, 'col' => '-', 'field' => 'DATABASE',
                            'value' => $stmt_add->error, 'type' => 'query_error',
                            'message' => "Baris $i: Gagal insert ke database",
                            'suggestion' => "Periksa koneksi database atau struktur tabel data_label_add."
                        ];
                    } else {
                        $importedCount++;
                    }
                }
            }
            $stmt_add->close();
        }

        $allErrors = array_merge($validationErrors, $rowErrors, $queryErrors);
        
        if (!empty($rowErrors) || !empty($queryErrors)) {
            unset($data);
            @unlink($targetFile);
            return [
                'success' => false,
                'errors' => array_slice($allErrors, 0, 50),
                'total_errors' => count($allErrors),
                'dataType' => $dataType
            ];
        }

        if ($importedCount == 0) {
            unset($data);
            @unlink($targetFile);
            $msg = $skippedCount > 0
                ? "Semua baris ($skippedCount) dilewati karena kolom ID kosong."
                : "Tidak ada data yang dapat diimport. Semua baris memiliki NO_URUT kosong.";
            return [
                'success' => false,
                'errors' => [
                    [
                        'row' => '-', 'col' => '-', 'field' => 'DATA', 'value' => '0 records',
                        'type' => 'no_data',
                        'message' => $msg,
                        'suggestion' => "Pastikan kolom NO_URUT dan ID diisi dengan nilai yang valid."
                    ]
                ],
                'dataType' => $dataType
            ];
        }

        $versionStmt = $db->prepare("INSERT INTO version (UPLOAD_VERSION, DATE_UPLOAD, DATA) VALUES (?, ?, ?)");
        $versionStmt->bind_param('sss', $UPLOAD_VERSION, $dateNowTime, $dataType);
        $versionResult = $versionStmt->execute();
        if (!$versionResult) {
            unset($data);
            @unlink($targetFile);
            return [
                'success' => false,
                'errors' => [
                    [
                        'row' => '-', 'col' => '-', 'field' => 'VERSION',
                        'value' => $versionStmt->error, 'type' => 'query_error',
                        'message' => "Gagal menyimpan versi upload",
                        'suggestion' => "Periksa koneksi database atau struktur tabel version."
                    ]
                ],
                'dataType' => $dataType
            ];
        }
        $versionStmt->close();

        unset($data);

        $uploadTarget = BASE_PATH . "/FILE/UPLOAD/" . $UPLOAD_VERSION . "-" . $safeName;
        if (!@rename($targetFile, $uploadTarget)) {
            $targetFileEscaped = str_replace("\\", "/", $targetFile);
            $uploadTargetEscaped = str_replace("\\", "/", $uploadTarget);
            if (!@rename($targetFileEscaped, $uploadTargetEscaped)) {
                copy($targetFile, $uploadTarget);
                @unlink($targetFile);
            }
        }

        return [
            'success' => true,
            'version' => $UPLOAD_VERSION,
            'imported' => $importedCount,
            'skipped' => $skippedCount,
            'message' => 'UPLOAD VERSION ANDA : ' . $UPLOAD_VERSION
        ];
    }
}
