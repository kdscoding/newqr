<div class="hero-section">
  <h1>Import Data</h1>
  <p>Upload file Excel sesuai template untuk memproses data QR code</p>
</div>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>

<?php $flash = Flash::get(); ?>
<?php if ($flash && $flash['type'] == 'success' && isset($flash['message'][0])): ?>
  <div class="alert alert-success">
    <strong>Berhasil!</strong> <?= htmlspecialchars($flash['message'][0]) ?>
  </div>
<?php elseif ($flash && $flash['type'] == 'error' && isset($flash['message'])): ?>
  <div class="alert alert-danger">
    <strong>Gagal!</strong>
    <ul>
      <?php foreach ($flash['message'] as $err): ?>
        <li><?= htmlspecialchars($err) ?></li>
      <?php endforeach; ?>
    </ul>
    <p>Pastikan file yang diupload sesuai dengan format yang diharapkan. Format Cells harus sesuai Contoh!</p>
    <a class="btn btn-primary btn-sm" href="/newqr/ContohQR_Inhouse.xls">CONTOH INHOUSE</a>
    <a class="btn btn-warning btn-sm" href="/newqr/ContohQR_SBSITE.xls">CONTOH SB SITE</a>
    <a class="btn btn-success btn-sm" href="/newqr/ContohQR_PAXAR.xls">CONTOH PAXAR</a>
    <a class="btn btn-info btn-sm" href="/newqr/ContohQR_TL.xls">CONTOH TL</a>
    <a class="btn btn-danger btn-sm" href="/newqr/ContohQR_Additional.xls">CONTOH ADDITIONAL</a>
  </div>
<?php endif; ?>

<div class="card" style="border-left:4px solid #ef4444;margin-bottom:24px;">
  <div class="card-header" style="color:#dc2626;">⚠️ PERHATIAN - FORMAT EXCEL WAJIB SESUAI TEMPLATE</div>
  <ul style="color:var(--text-muted);font-size:14px;line-height:1.8;margin:0;padding-left:20px;">
    <li><strong>Format Cells harus sesuai template</strong> (case-sensitive)</li>
    <li><strong>Kolom header harus EXACT sesuai template</strong> (case-sensitive)</li>
    <li>Jumlah kolom harus sesuai template masing-masing tipe data</li>
    <li>File yang tidak sesuai format akan <strong>DITOLAK</strong> dan tidak akan diproses</li>
  </ul>
</div>

<div class="card" style="margin-bottom:24px;">
  <div class="card-header">📄 Template Excel</div>
  <div class="row">
    <div class="col-sm-6 col-md-2">
      <div class="template-card">
        <div style="font-size:36px;margin-bottom:8px;">🏠</div>
        <div style="font-weight:700;font-size:14px;margin-bottom:4px;">Inhouse</div>
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">Format data inhouse</div>
        <a class="btn btn-primary btn-sm" href="/newqr/ContohQR_Inhouse.xls" role="button">Download</a>
      </div>
    </div>
    <div class="col-sm-6 col-md-2">
      <div class="template-card">
        <div style="font-size:36px;margin-bottom:8px;">🏗️</div>
        <div style="font-weight:700;font-size:14px;margin-bottom:4px;">SB SITE</div>
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">Format data site builder</div>
        <a class="btn btn-primary btn-sm" href="/newqr/ContohQR_SBSITE.xls" role="button">Download</a>
      </div>
    </div>
    <div class="col-sm-6 col-md-2">
      <div class="template-card">
        <div style="font-size:36px;margin-bottom:8px;">📦</div>
        <div style="font-weight:700;font-size:14px;margin-bottom:4px;">PAXAR</div>
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">Format data paxar label</div>
        <a class="btn btn-primary btn-sm" href="/newqr/ContohQR_PAXAR.xls" role="button">Download</a>
      </div>
    </div>
    <div class="col-sm-6 col-md-2">
      <div class="template-card">
        <div style="font-size:36px;margin-bottom:8px;">🏷️</div>
        <div style="font-weight:700;font-size:14px;margin-bottom:4px;">Trigger Label</div>
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">Format data trigger label</div>
        <a class="btn btn-primary btn-sm" href="/newqr/ContohQR_TL.xls" role="button">Download</a>
      </div>
    </div>
    <div class="col-sm-6 col-md-2">
      <div class="template-card">
        <div style="font-size:36px;margin-bottom:8px;">➕</div>
        <div style="font-weight:700;font-size:14px;margin-bottom:4px;">Additional Label</div>
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">Format data additional label</div>
        <a class="btn btn-primary btn-sm" href="/newqr/ContohQR_Additional.xls" role="button">Download</a>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">⬆️ Upload File Excel</div>
  <div class="notification-area" id="notificationArea"></div>
  <form method="POST" action="/newqr/actions/import.php" enctype="multipart/form-data" id="importForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <div class="row">
      <div class="col-md-6">
        <div class="form-group">
          <label for="data">Tipe Data</label>
          <select class="form-control" name="data" id="data" required>
            <option value="inhouse">Inhouse</option>
            <option value="sbsite">SB SITE</option>
            <option value="paxar">PAXAR</option>
            <option value="tl">TRIGER LABEL</option>
            <option value="additional_label">Additional Label</option>
          </select>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-group">
          <label for="file_excel">File Excel (*.xls)</label>
          <input type="file" class="form-control" id="file_excel" name="file_excel" placeholder="Upload File" accept=".xls" required>
        </div>
      </div>
    </div>
    <div class="row" style="margin-top:20px;">
      <div class="col-md-4 col-md-offset-4">
        <button type="submit" id="btnSubmit" class="btn btn-success" style="width:100%;">Upload & Import</button>
      </div>
    </div>
  </form>
</div>
