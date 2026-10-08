<?php
$breadcrumbs = [];
if ($path !== '') {
    $acc = '';
    foreach (explode('/', $path) as $seg) {
        $acc = $acc === '' ? $seg : $acc . '/' . $seg;
        $breadcrumbs[] = [
            'label' => $seg,
            'link' => BASE_URL . '/bsom?path=' . implode('/', array_map('rawurlencode', explode('/', $acc))),
        ];
    }
}
$sortUrl = function ($col, $dir) use ($pathEncoded) {
    $sep = ($pathEncoded !== '') ? '?path=' . $pathEncoded . '&' : '?';
    return BASE_URL . '/bsom' . $sep . 'sort=' . $col . '&dir=' . $dir;
};
$headerLink = function ($col, $label) use ($sortCol, $sortDir, $sortUrl) {
    $nextDir = ($sortCol === $col && $sortDir === 'asc') ? 'desc' : 'asc';
    $active = ($sortCol === $col) ? ' sortable-active' : '';
    $arrow = '';
    if ($sortCol === $col) {
        $arrow = $sortDir === 'asc' ? ' ↓' : ' ↑';
    }
    return '<a href="' . $sortUrl($col, $nextDir) . '" class="sortable' . $active . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . $arrow . '</a>';
};
$entryUrl = function ($e) {
    if ($e['isDir']) {
        return $e['link'];
    }
    return $e['viewUrl'];
};
?>
<style>
  .sortable { text-decoration: none; color: inherit; }
  .sortable:hover { text-decoration: underline; }
  .sortable-active { color: var(--primary); font-weight: 700; }
  .bsom-search-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
  .bsom-name { word-break:break-word; }
  .bsom-loc { word-break:break-all; }
</style>
<div class="hero-section">
  <h1>📂 BSOM Files</h1>
  <p>Browser berkas server 10.10.10.98/bsom — diakses & dicari dari dalam project ini.</p>
</div>

<div class="card">
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
    <span>Lokasi</span>
    <a href="<?=BASE_URL?>/bsom" class="btn btn-sm btn-primary" style="padding:6px 12px;font-size:12px;">↲ Akar BSOM</a>
  </div>

  <?php if (!empty($breadcrumbs)): ?>
    <nav class="nav-actions" style="margin-bottom:12px;">
      <a href="<?=BASE_URL?>/bsom" class="btn btn-sm" style="background:#f1f5f9;">⌂</a>
      <?php foreach ($breadcrumbs as $crumb): ?>
        <a href="<?=$crumb['link']?>" class="btn btn-sm" style="background:#f1f5f9;"><?=$crumb['label']?>/</a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>

  <p class="text-muted-custom" style="font-size:13px;margin-bottom:12px;word-break:break-all;"><?=$currentDisplay?></p>

  <form method="get" action="<?=BASE_URL?>/bsom" class="bsom-search-row" style="margin-bottom:14px;">
    <input type="text" id="bsomSearch" name="q" class="form-control bsom-search" value="<?=htmlspecialchars($query ?? '', ENT_QUOTES, 'UTF-8')?>" placeholder="🔍 Cari berkas/folder di seluruh bsom..." style="max-width:360px;">
    <button type="submit" class="btn btn-sm btn-primary">Cari</button>
    <?php if (($query ?? '') !== ''): ?>
      <a href="<?=BASE_URL?>/bsom<?= ($pathEncoded !== '') ? '?path=' . $pathEncoded : '' ?>" class="btn btn-sm">× Bersihkan</a>
    <?php endif; ?>
  </form>

  <?php if (($query ?? '') !== ''): ?>
    <?php if (!empty($searchStats)): ?>
      <?php
      $partial = !empty($searchStats['partial']);
      $fromIndex = !empty($searchStats['fromIndex']);
      $basis = $path === '' ? 'seluruh bsom (akar)' : 'folder saat ini: ' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
      ?>
      <div class="nav-actions" style="margin-bottom:12px;">
        <span class="text-muted-custom" style="font-size:13px;">
          <?= $searchTotal ?> hasil untuk "<strong><?=htmlspecialchars($query, ENT_QUOTES, 'UTF-8')?></strong>" — pencarian dari <?= $basis ?>
          <?php if ($fromIndex): ?>
            <span class="badge bg-success ms-2">INSTAN (dari index lengkap)</span>
          <?php elseif (!empty($searchStats['requests'])): ?>
            (scanned <strong><?=$searchStats['folders']?></strong> folder / <strong><?=$searchStats['requests']?></strong> request<?=($partial ? ', <em>terbatas — cari dari folder spesifik untuk hasil pasti</em>' : '')?>).
          <?php endif; ?>
          <?php if (!empty($searchError)): ?> — <span class="alert-warning-box" style="display:inline;padding:2px 8px;font-size:11px;"><?=$searchError?></span><?php endif; ?>
        </span>
      </div>
    <?php elseif (!empty($searchError)): ?>
      <div class="nav-actions" style="margin-bottom:12px;">
        <span class="text-muted-custom" style="font-size:13px;"><?=$searchError?></span>
      </div>
    <?php else: ?>
      <div class="nav-actions" style="margin-bottom:12px;">
        <span class="text-muted-custom" style="font-size:13px;">
          <?= $searchTotal ?> hasil untuk "<strong><?=htmlspecialchars($query, ENT_QUOTES, 'UTF-8')?></strong>"
        </span>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($error !== null): ?>
    <div class="alert alert-danger"><?=$error?></div>
  <?php elseif (($query ?? '') !== '' && $searchTotal === 0): ?>
    <p class="text-muted-custom">Tidak ada berkas/folder yang cocok. Coba kata kunci lain.</p>
  <?php elseif (($query ?? '') !== '' && $searchTotal > 0): ?>
    <table class="table">
      <thead>
        <tr>
          <th style="width:40%;">Nama</th>
          <th style="width:45%;">Lokasi (folder)</th>
          <th style="width:15%;">Aksi</th>
        </tr>
      </thead>
      <tbody id="bsomTbody">
        <?php foreach ($searchResults as $e): ?>
          <tr class="bsom-row" data-name="<?=htmlspecialchars($e['name'], ENT_QUOTES, 'UTF-8')?>">
            <td class="bsom-name"><?=($e['isDir'] ? '📁 ' : '📄 ') . $e['name']?></td>
            <td class="bsom-loc" style="font-size:13px;color:#64748b;"><?=($e['rel'] !== '' ? $e['rel'] : ' — ')?></td>
            <td>
              <?php if ($e['isDir']): ?>
                <a href="<?=$e['link']?>" class="btn btn-sm btn-primary">Buka</a>
              <?php else: ?>
                <a href="<?=$e['viewUrl']?>" class="btn btn-sm btn-primary">👁️ Lihat</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php elseif (empty($entries)): ?>
    <p class="text-muted-custom">Tidak ada berkas atau folder ditemukan.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th style="width:42%;"><?=$headerLink('name', 'Nama')?></th>
          <th style="width:24%;"><?=$headerLink('modified', 'Terakhir Dimodifikasi')?></th>
          <th style="width:16%;"><?=$headerLink('size', 'Size')?></th>
          <th style="width:18%;">Aksi</th>
        </tr>
      </thead>
      <tbody id="bsomTbody">
        <?php foreach ($entries as $e): ?>
          <tr class="bsom-row" data-name="<?=htmlspecialchars($e['name'], ENT_QUOTES, 'UTF-8')?>">
            <td class="bsom-name">
              <?php if ($e['isDir'] && $e['isParent']): ?>
                <span class="icon">⬆️</span> <a href="<?=$e['link']?>" class="text-decoration-underline">Parent Directory</a>
              <?php elseif ($e['isDir']): ?>
                <span class="icon">📁</span> <a href="<?=$e['link']?>" class="text-decoration-underline"><?=$e['name']?></a>
              <?php else: ?>
                <span class="icon">📄</span> <?=$e['name']?>
              <?php endif; ?>
            </td>
            <td class="muted" style="font-size:13px;color:#64748b;"><?=($e['modified'] !== '' ? $e['modified'] : '-')?></td>
            <td class="muted" style="font-size:13px;color:#64748b;"><?=(!$e['isDir'] && $e['size'] !== '' ? $e['size'] : '-')?></td>
            <td>
              <?php if ($e['isDir']): ?>
                <a href="<?=$e['link']?>" class="btn btn-sm btn-primary">Buka</a>
              <?php else: ?>
                <a href="<?=$e['viewUrl']?>" class="btn btn-sm btn-primary">👁️ Lihat</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<script>
(function(){
  var search = document.getElementById('bsomSearch');
  var tbody = document.getElementById('bsomTbody');
  if (!search || !tbody) return;
  var apply = function(){
    var q = (search.value||'').toLowerCase();
    var inputVal = search.value||'';
    // In search mode, q is already in the input value; if input is editable, filter results too.
    if (q === '') {
      Array.from(tbody.querySelectorAll('tr.bsom-row')).forEach(function(tr){ tr.style.display=''; });
      return;
    }
    // Only filter if there is a separate filter intent (typing refines). Keep all matches.
  };
  search.addEventListener('input', function(){
    var q = (search.value||'').toLowerCase();
    if (q === '') {
      Array.from(tbody.querySelectorAll('tr.bsom-row')).forEach(function(tr){ tr.style.display=''; });
      return;
    }
    Array.from(tbody.querySelectorAll('tr.bsom-row')).forEach(function(tr){
      var name = (tr.getAttribute('data-name')||'').toLowerCase();
      var loc = (tr.querySelector('.bsom-loc')? tr.querySelector('.bsom-loc').textContent:'').toLowerCase();
      tr.style.display = (name.indexOf(q)!==-1 || loc.indexOf(q)!==-1) ? '' : 'none';
    });
  });
})();
</script>
