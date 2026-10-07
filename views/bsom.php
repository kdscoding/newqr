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
$baseQuery = ($pathEncoded !== '') ? '?path=' . $pathEncoded : '';
$sortUrl = function ($col, $dir) use ($baseQuery) {
    $sep = $baseQuery === '' ? '?' : $baseQuery . '&';
    return BASE_URL . '/bsom' . $sep . 'sort=' . $col . '&dir=' . $dir;
};
$headerLink = function ($col, $label) use ($sortCol, $sortDir, $sortUrl) {
    $nextDir = 'asc';
    if ($sortCol === $col && $sortDir === 'asc') {
        $nextDir = 'desc';
    }
    $active = ($sortCol === $col) ? ' sortable-active' : '';
    $arrow = '';
    if ($sortCol === $col) {
        $arrow = $sortDir === 'asc' ? ' ↓' : ' ↑';
    }
    return '<a href="' . $sortUrl($col, $nextDir) . '" class="sortable' . $active . '">' . $label . $arrow . '</a>';
};
?>
<style>
  .sortable { text-decoration: none; color: inherit; }
  .sortable:hover { text-decoration: underline; }
  .sortable-active { color: var(--primary); font-weight: 700; }
  .bsom-name { word-break: break-word; }
</style>
<div class="hero-section">
  <h1>📂 BSOM Files</h1>
  <p>Browser berkas server 10.10.10.98/bsom — diakses dari dalam project ini.</p>
</div>

<div class="card">
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
    <span>Lokasi</span>
    <a href="<?=BASE_URL?>/bsom" class="btn btn-sm btn-primary" style="padding:6px 12px;font-size:12px;">↲ Akar BSOM</a>
  </div>

  <?php if (!empty($breadcrumbs)): ?>
    <nav class="nav-actions" style="margin-bottom:12px;">
      <a href="/newqr/bsom" class="btn btn-sm" style="background:#f1f5f9;">⌂</a>
      <?php foreach ($breadcrumbs as $crumb): ?>
        <a href="<?=$crumb['link']?>" class="btn btn-sm" style="background:#f1f5f9;"><?=$crumb['label']?>/</a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>

  <p class="text-muted-custom" style="font-size:13px;margin-bottom:12px;word-break:break-all;"><?=$currentDisplay?></p>

  <?php if ($error !== null): ?>
    <div class="alert alert-danger"><?=$error?></div>
  <?php elseif (empty($entries)): ?>
    <p class="text-muted-custom">Tidak ada berkas atau folder ditemukan.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th style="width:45%;"><?=$headerLink('name', 'Nama')?></th>
          <th style="width:22%;"><?=$headerLink('modified', 'Terakhir Dimodifikasi')?></th>
          <th style="width:15%;"><?=$headerLink('size', 'Size')?></th>
          <th style="width:18%;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($entries as $e): ?>
          <tr>
            <td class="bsom-name">
              <?php if ($e['isDir']): ?>
                <a href="<?=$e['link']?>" class="btn btn-link btn-sm" style="font-weight:600;text-decoration:underline;padding:0 4px;">
                  <span class="icon">📁</span> <?=$e['name']?>
                </a>
              <?php else: ?>
                <span class="icon">📄</span> <?=$e['name']?>
              <?php endif; ?>
            </td>
            <td style="font-size:13px;color:#64748b;"><?=($e['modified'] !== '' ? $e['modified'] : '-')?></td>
            <td style="font-size:13px;color:#64748b;"><?=(!$e['isDir'] && $e['size'] !== '' ? $e['size'] : '-')?></td>
            <td>
              <?php if ($e['isDir']): ?>
                <a href="<?=$e['link']?>" class="btn btn-sm btn-primary">Buka</a>
              <?php else: ?>
                <a href="<?=$e['download']?>" class="btn btn-sm btn-success">⬇️ Unduh</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
