<?php
$v = $viewData;
?>
<div class="hero-section">
  <h1>👁️ Lihat Berkas</h1>
  <p>Preview dari server bsom.</p>
</div>

<div class="card">
  <div class="card-header">Detail Berkas</div>

  <?php if ($v['error'] !== null): ?>
    <div class="alert alert-danger"><?=$v['error']?></div>
  <?php else: ?>
    <div class="nav-actions" style="flex-wrap:wrap;gap:6px;margin-bottom:14px;">
      <span style="font-weight:600;"><?=$v['name']?></span>
      <span class="text-muted-custom" style="font-size:12px;"><?=($v['size'] !== '' ? $v['size'] : '-')?> · <?=$v['mime']?></span>
    </div>

    <div class="preview-area" style="border:1px solid var(--border);border-radius:10px;padding:14px;min-height:140px;overflow:auto;">
      <?php if ($v['viewType'] === 'text'): ?>
        <pre class="text-muted-custom" style="white-space:pre-wrap;word-break:break-word;margin:0;font-size:13px;line-height:1.5;"><?=htmlspecialchars($v['preview'], ENT_QUOTES, 'UTF-8')?></pre>
      <?php elseif ($v['viewType'] === 'csv'): ?>
        <table class="table" style="font-size:13px;">
          <?php
          $rows = preg_split('/\r\n|\r|\n/', trim($v['preview']));
            $i = 0;
            foreach ($rows as $r) {
                if (trim($r) === '') continue;
                $cells = str_getcsv($r);
                echo '<tr>';
                foreach ($cells as $c) {
                    echo ($i === 0 ? '<th>' : '<td>') . htmlspecialchars($c, ENT_QUOTES, 'UTF-8') . ($i === 0 ? '</th>' : '</td>');
                }
                echo '</tr>';
                $i++;
            }
          ?>
        </table>
      <?php elseif ($v['viewType'] === 'image'): ?>
        <img src="<?=$v['streamUrl']?>" style="max-width:100%;height:auto;border-radius:8px;" alt="<?=htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8')?>">
      <?php elseif ($v['viewType'] === 'pdf'): ?>
        <iframe src="<?=$v['streamUrl']?>" type="application/pdf" style="width:100%;height:640px;border:none;border-radius:8px;"></iframe>
      <?php else: ?>
        <div class="alert">📄 Berkas bertipe ini tidak dapat dilihat langsung di perambalan. Unduh berkas di bawah jika diperlukan.</div>
      <?php endif; ?>
    </div>

    <div style="margin-top:14px;">
      <a href="<?=$v['downloadUrl']?>" class="btn btn-success">⬇️ Unduh Berkas</a>
      <a href="<?=BASE_URL?>/bsom" class="btn btn-sm btn-primary">← Kembali ke Browser BSOM</a>
    </div>
  <?php endif; ?>
</div>
