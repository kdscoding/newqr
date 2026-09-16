<button onclick="window.print()" class="btn-success" style="margin-bottom:10px;">🖨 PRINT</button>

<div class="warning-header">
  <strong>⚠️ CEK KEMBALI DAN PASTIKAN ITEM, QTY DAN TOTAL PO SESUAI DENGAN CODE DAN TANGGAL PENGAMBILAN HARI INI ⚠️<br>
    ⚠️ KONFIRMASI JIKA CODE ITEM/ REMARK BERBEDA DENGAN YANG DIAMBIL ⚠️<br>
    ⚠️ CODE Leather Care HT BISA DI CEK DI PACKING LIST SYSTEM (KONFIRMASI JIKA TIDAK ADA) ⚠️</strong>
</div>
<div class="remark-text">
  <b>KETERANGAN : </b><b>WIE - 20030004/ SRB-APR26</b> = SERBIA PODD APRIL '26, <b>WIE - 20030004 / V27</b> = SPAIN 31 JULI '26
  <br>
  <b>VIVI-St-En</b> = Stiker VIVI USA, <b>VIVI-St-EnFr</b> = Stiker VIVI Canada
</div>

<div class="label-container">
  <?php
  if ($cek_data && $cek_data["DATA"] === "additional_label") {
    $tampil = mysqli_query($db, "SELECT * FROM data_label_add WHERE UPLOAD_VERSION='$version' ORDER BY NO_URUT ASC");

    $i = 0;
    while ($r = mysqli_fetch_array($tampil)) {
      $id = $r['ID'];
      $qrPath = BASE_PATH . "/QR/{$id}.png";
      $qrUrl = BASE_URL . "/QR/{$id}.png";
      if (!file_exists($qrPath)) {
        require_once BASE_PATH . '/phpqrcode/qrlib.php';
        QRcode::png($id, $qrPath, QR_ECLEVEL_M, 4);
      }

      $cell = strtoupper(trim($r['BUILDING']));
      $cellClass = in_array($cell, ['A', 'B', 'C', 'D', 'E', 'H']) ? "cell-$cell" : 'cell-default';

      $baris_ke = floor($i / 4);
      $rowDir = ($baris_ke % 2 === 0) ? 'row-ltr' : 'row-rtl';

      echo "<div class='label-box $cellClass $rowDir'>
      <div class='label-content'>
      <div class='qr-wrapper'>
      <img src='$qrUrl' class='qr-img' alt='QR Code'>
      <div class='cell-text'>" . htmlspecialchars($r['CELL']) . "</div>
      </div>
      <div class='label-text'>
      <div class='info-row'><strong>PO#</strong><span>:</span><span class='po-item'>" . htmlspecialchars($r['PO']) . "</span></div>
      <div class='info-row'><strong>SAP</strong><span>:</span><span class='po-item'>" . htmlspecialchars($r['SAP']) . "</span></div>
      <div class='info-row'><strong>ITEM</strong><span>:</span><span class='po-item'>" . htmlspecialchars($r['ITEM']) . "</span></div>
      
      <div class='info-row'><strong>REMARK</strong><span>:</span><span class='po-item'>" . htmlspecialchars($r['PACKING_LIST']) . "</span></div>
      <div class='info-row'><strong>CNTRY</strong><span>:</span><span class='po-item'>" . htmlspecialchars($r['COUNTRY']) . "</span></div>
      <div class='info-row'><strong>QTY</strong><span>:</span><span class='po-item'>{$r['QTY']}</span></div>
      <div class='info-row'><strong>TAKEN</strong><span>:</span><span class='po-item'>" . htmlspecialchars($r['TAKEN_BY']) . "</span></div>
      </div>
      </div>
      <div class='label-no'>" . htmlspecialchars($r['NO_URUT']) . "</div>
      </div>";

      $i++;
    }
  } else {
    echo "<p>Data tidak ditemukan atau versi salah.</p>";
  }
  ?>
</div>
<style type="text/css">
  body {
    font-family: Arial, sans-serif;
    font-size: 7px;
    margin: 0;
  }

  .warning-header {
    background-color: transparent;
    color: #000;
    font-size: 11px;
    font-weight: bold;
    padding: 12px;
    border: 3px solid #000;
    margin-bottom: 10px;
    text-align: center;
    text-transform: uppercase;
    page-break-inside: avoid;
  }

  .remark-text {
    font-size: 11px;
    margin-top: 4px;
    color: #333;
    text-align: left;
    text-transform: none;
  }

  @media print {
    button {
      display: none;
    }

    body {
      margin: 0.2cm;
    }

    .warning-header {
      font-size: 11px;
      background-color: transparent !important;
      color: #000 !important;
      border: 3px solid #000 !important;
      page-break-inside: avoid;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      text-align: center !important;
    }

    .remark-text {
      font-size: 11px !important;
      color: #333 !important;
      text-align: left !important;
      text-transform: none !important;
      margin-top: 4px !important;
    }

    * {
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    .label-container {
      gap: 6px;
    }

    .label-box {
      width: calc((100% / 4) - 6px);
    }
  }

  .label-container {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: flex-start;
    width: 100.50%;
  }

  .label-box {
    width: calc((100% / 4) - 6px);
    border: 1px solid #000;
    border-left-width: 8px;
    padding: 5px;
    height: 85px;
    display: flex;
    flex-direction: column;
    position: relative;
    page-break-inside: avoid;
    overflow: hidden;
    margin: 0;
  }

  .label-no {
    position: absolute;
    bottom: 3px;
    right: 5px;
    font-size: 6px;
    font-weight: bold;
    color: #000;
  }

  .cell-A.label-box { border-left-color: rgb(210, 222, 50); }
  .cell-B.label-box { border-left-color: yellow; }
  .cell-C.label-box { border-left-color: red; }
  .cell-D.label-box { border-left-color: rgb(178, 178, 178); }
  .cell-E.label-box { border-left-color: orange; }
  .cell-H.label-box { border-left-color: rgb(0, 169, 255); }
  .cell-default.label-box { border-left-color: #eee; }

  .label-content {
    display: flex;
    align-items: center;
    height: 100%;
  }

  .row-ltr .qr-wrapper {
    order: 1;
    margin-right: 3px;
  }

  .row-ltr .label-text {
    order: 2;
  }

  .row-rtl .label-text {
    order: 1;
    padding-right: 4px;
  }

  .row-rtl .qr-wrapper {
    order: 2;
  }

  .qr-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 45px;
    flex-shrink: 0;
    min-width: 45px;
  }

  .qr-img {
    width: 45px;
    height: 45px;
    object-fit: contain;
  }

  .cell-text {
    font-size: 9px;
    margin-top: 3px;
    font-weight: bold;
    text-align: center;
  }

  .label-text {
    flex-grow: 1;
    line-height: 1.2;
    font-size: 7px;
    max-width: calc(100% - 45px);
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .info-row {
    display: grid;
    grid-template-columns: 32px 0px 1fr;
    align-items: start;
  }

  .info-row strong {
    font-weight: bold;
    white-space: nowrap;
  }

  .info-row .po-item {
    word-break: break-word;
    padding-left: 2px;
  }

  .label-text strong {
    font-weight: bold;
  }

  .label-text .po-item {
    font-size: 8px;
    font-weight: bold;
  }

  .label-text .packing {
    font-size: 8px;
    font-weight: 600;
  }
</style>