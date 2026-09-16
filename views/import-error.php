<div class="container">
  <div class="alert alert-danger">
    <h4>Format Excel Tidak Sesuai</h4>
    <p>File yang diupload tidak sesuai dengan format yang diharapkan untuk tipe data <b><?= htmlspecialchars($dataType) ?></b>.</p>
    <ul>
      <?php foreach ($validationErrors as $err): ?>
        <li><?= htmlspecialchars($err) ?></li>
      <?php endforeach; ?>
    </ul>
    <p>Pastikan file yang diupload sesuai dengan format yang diharapkan. Format Cells harus sesuai Contoh!</p>
    <a class="btn btn-primary btn-lg mb-5 mb-lg-2" href="/newqr/ContohQR_Inhouse.xls" role="button">CONTOH INHOUSE</a>
    <a class="btn btn-warning btn-lg mb-5 mb-lg-2" href="/newqr/ContohQR_SBSITE.xls" role="button">CONTOH SB SITE</a>
    <a class="btn btn-success btn-lg mb-5 mb-lg-2" href="/newqr/ContohQR_PAXAR.xls" role="button">CONTOH PAXAR</a>
    <a class="btn btn-info btn-lg mb-5 mb-lg-2" href="/newqr/ContohQR_TL.xls" role="button">CONTOH TL</a>
  </div>
</div>
