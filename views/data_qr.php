<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>
<div class="page-header">
  <h2>Data QR</h2>
</div>

<?php if ($msg && $msg['type'] == 'deleted'): ?>
  <div class="alert alert-success">Data berhasil dihapus.</div>
<?php elseif ($msg && $msg['type'] == 'error'): ?>
  <div class="alert alert-danger">Gagal menghapus data.</div>
<?php endif; ?>

<div class="card">
  <div class="card-header">Daftar Upload Version Hari Ini</div>
  <table id="dataqr" class="table table-striped table-bordered" style="width:100%">
    <thead>
      <tr>
        <th>NO</th>
        <th>UPLOAD VERSION</th>
        <th>DATE UPLOAD</th>
        <th>DATA</th>
        <th>PRINT</th>
        <th>HAPUS</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $i = 1;
      foreach ($rows as $r) {
      ?>
        <tr>
          <td><?= $i; ?></td>
          <td>
            <?= htmlspecialchars($r['UPLOAD_VERSION'], ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td><?= htmlspecialchars($r['DATE_UPLOAD'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($r['DATA'], ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <div class="dropdown">
              <button class="btn btn-sm btn-success dropdown-toggle" type="button" id="printMenu<?= $i ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                PRINT <span class="caret"></span>
              </button>
              <ul class="dropdown-menu" aria-labelledby="printMenu<?= $i ?>">
                <?php if ($r['DATA'] != "additional_label") { ?>
                  <li><a href="/newqr/print/<?= htmlspecialchars($r['UPLOAD_VERSION'], ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars($r['DATA'], ENT_QUOTES, 'UTF-8') ?>" rel="noopener" target="_blank">PRINT</a></li>
                <?php } ?>
                <?php if ($r['DATA'] != "paxar" && $r['DATA'] != "additional_label" && $r['DATA'] != "tl") { ?>
                  <li><a href="/newqr/print-xerox/<?= htmlspecialchars($r['UPLOAD_VERSION'], ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars($r['DATA'], ENT_QUOTES, 'UTF-8') ?>" rel="noopener" target="_blank">XEROX</a></li>
                <?php } ?>
                <?php if ($r['DATA'] == "additional_label") { ?>
                  <li><a href="/newqr/print/<?= htmlspecialchars($r['UPLOAD_VERSION'], ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars($r['DATA'], ENT_QUOTES, 'UTF-8') ?>_left" rel="noopener" target="_blank">PRINT QR KIRI</a></li>
                <?php } ?>
              </ul>
            </div>
          </td>
          <td>
            <form method="POST" action="/newqr/actions/delete.php" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus data <?= htmlspecialchars($r['UPLOAD_VERSION'], ENT_QUOTES, 'UTF-8') ?>\nData yang dihapus tidak bisa dikembalikan!');">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="version" value="<?= htmlspecialchars($r['UPLOAD_VERSION'], ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="data" value="<?= htmlspecialchars($r['DATA'], ENT_QUOTES, 'UTF-8') ?>">
              <button type="submit" class="btn btn-sm btn-danger btn-delete">HAPUS</button>
            </form>
          </td>
        </tr>
      <?php
        $i++;
      }
      ?>
    </tbody>
  </table>
</div>