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
  .sortable {
    text-decoration: none;
    color: inherit;
  }

  .sortable:hover {
    text-decoration: underline;
  }

  .sortable-active {
    color: var(--primary);
    font-weight: 700;
  }

  .bsom-search-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .bsom-name {
    word-break: break-word;
  }

  .bsom-loc {
    word-break: break-all;
  }
</style>
<div class="hero-section" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
  <h1 style="margin:0;">📂 BSOM Files <span class="text-muted-custom" style="font-weight:400;font-size:0.6em;">http://10.10.10.98/bsom/</span></h1>
  <?php if (!empty($breadcrumbs)): ?>
    <nav class="nav-actions" style="margin:0;display:flex;align-items:center;gap:4px;">
      <a href="<?= BASE_URL ?>/bsom" class="btn btn-sm" style="background:#f1f5f9;padding:4px 8px;font-size:11px;">⌂</a>
      <?php foreach ($breadcrumbs as $crumb): ?>
        <a href="<?= $crumb['link'] ?>" class="btn btn-sm" style="background:#f1f5f9;padding:4px 8px;font-size:11px;"><?= $crumb['label'] ?>/</a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
    <span>Lokasi</span>
    <a href="<?= BASE_URL ?>/bsom" class="btn btn-sm btn-primary" style="padding:6px 12px;font-size:12px;">↲ Akar BSOM</a>
  </div>

  <p class="text-muted-custom" style="font-size:13px;margin-bottom:12px;word-break:break-all;"><?= $currentDisplay ?></p>

  <form method="get" action="<?= BASE_URL ?>/bsom" class="bsom-search-row" style="margin-bottom:14px;">
    <input type="text" id="bsomSearch" name="q" class="form-control bsom-search" value="<?= htmlspecialchars($query ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="🔍 Cari berkas/folder di seluruh bsom..." style="max-width:360px;">
    <button type="submit" class="btn btn-sm btn-primary">Cari</button>
    <?php if (($query ?? '') !== ''): ?>
      <a href="<?= BASE_URL ?>/bsom<?= ($pathEncoded !== '') ? '?path=' . $pathEncoded : '' ?>" class="btn btn-sm">× Bersihkan</a>
    <?php endif; ?>
  </form>

  <?php if (($query ?? '') !== ''): ?>
    <?php if (!empty($searchStats)): ?>
      <?php
      $partial = !empty($searchStats['partial']);
      $fromIndex = !empty($searchStats['fromIndex']);
      $basis = $path === '' ? 'seluruh bsom' : 'folder: ' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
      ?>
      <div class="nav-actions" style="margin-bottom:8px;">
        <span class="text-muted-custom" style="font-size:12px;">
          <strong><?= $searchTotal ?></strong> file cocok "<strong><?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?></strong>" di <?= $basis ?>
          <?php if (!$fromIndex && !empty($searchStats['requests'])): ?>
            <span class="text-warning"> — parsial (<?= $searchStats['folders'] ?> folder, <?= $searchStats['requests'] ?> req)</span>
          <?php endif; ?>
        </span>
      </div>
    <?php elseif (!empty($searchError)): ?>
      <div class="nav-actions" style="margin-bottom:8px;">
        <span class="text-muted-custom" style="font-size:12px;"><?= $searchError ?></span>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($error !== null): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php elseif (($query ?? '') !== '' && $searchTotal === 0): ?>
    <p class="text-muted-custom">Tidak ada berkas/folder yang cocok. Coba kata kunci lain.</p>
  <?php elseif (($query ?? '') !== '' && $searchTotal > 0): ?>
    <?php if ($autoGrouped && !empty($groupedResults)): ?>
      <style>
        .season-group {
          margin-bottom: 16px;
          border: 1px solid #e2e8f0;
          border-radius: 6px;
          overflow: hidden;
          background: #fff;
        }

        .season-header {
          background: #f8fafc;
          padding: 8px 14px;
          font-weight: 600;
          color: #1e293b;
          border-bottom: 1px solid #e2e8f0;
          font-size: 13px;
        }

        .season-header .count {
          font-weight: 400;
          color: #64748b;
          font-size: 11px;
          margin-left: 6px;
        }

        .file-row {
          display: flex;
          gap: 10px;
          padding: 8px 14px;
          border-bottom: 1px solid #f1f5f9;
          align-items: flex-start;
        }

        .file-row:last-child {
          border-bottom: none;
        }

        .file-row:hover {
          background: #f8fafc;
        }

        .file-icon {
          width: 24px;
          height: 24px;
          flex-shrink: 0;
          margin-top: 1px;
          font-size: 14px;
        }

        .file-info {
          flex: 1;
          min-width: 0;
        }

        .file-name {
          font-weight: 500;
          color: #1e293b;
          word-break: break-word;
          font-size: 13px;
        }

        .file-path {
          font-size: 10px;
          color: #94a3b8;
          word-break: break-all;
          margin-top: 1px;
        }

        .file-preview {
          margin-top: 6px;
          padding: 6px;
          background: #f8fafc;
          border: 1px solid #e2e8f0;
          border-radius: 4px;
        }

        .file-preview iframe {
          width: 100%;
          min-height: 450px;
          border: none;
          border-radius: 3px;
          background: #fff;
        }

        .file-preview img {
          width: 100%;
          max-height: 350px;
          border-radius: 3px;
          background: #f8fafc;
        }

        .file-preview pre {
          max-height: 250px;
          overflow: auto;
          font-size: 10px;
          margin: 0;
          background: #fff;
          padding: 6px;
          border-radius: 3px;
        }

        .file-actions {
          display: flex;
          gap: 5px;
          flex-shrink: 0;
          margin-top: 3px;
        }

        .btn-sm {
          padding: 4px 8px;
          font-size: 10px;
        }
      </style>
      <?php foreach ($groupedResults as $season => $files): ?>
        <div class="season-group">
          <div class="season-header">
            <?= htmlspecialchars($season, ENT_QUOTES, 'UTF-8') ?>
            <span class="count"><?= count($files) ?> file(s)</span>
          </div>
          <?php foreach ($files as $f):
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $isPdf = $ext === 'pdf';
            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']);
            $isText = in_array($ext, ['txt', 'log', 'csv', 'json', 'html', 'htm', 'css', 'js', 'xml', 'ini']);
            $encRel = implode('/', array_map('rawurlencode', explode('/', $f['rel'])));
            $viewUrl = BASE_URL . '/bsom/view?file=' . $encRel;
            $streamUrl = BASE_URL . '/actions/bsom-stream.php?file=' . $encRel;
            $downloadUrl = BASE_URL . '/actions/bsom-download.php?file=' . $encRel;
          ?>
            <div class="file-row">
              <span class="file-icon"><?= $isPdf ? '📄' : ($isImage ? '🖼️' : ($isText ? '📝' : '📄')) ?></span>
              <div class="file-info">
                <div class="file-name"><?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="file-path"><?= htmlspecialchars($f['rel'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php if ($isPdf): ?>
                  <div class="file-preview">
                    <iframe src="<?= $streamUrl ?>#toolbar=0&navpanes=0" title="<?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?>"></iframe>
                  </div>
                <?php elseif ($isImage): ?>
                  <div class="file-preview">
                    <img src="<?= $streamUrl ?>" alt="<?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                <?php elseif ($isText): ?>
                  <div class="file-preview">
                    <pre><?= htmlspecialchars(file_get_contents($streamUrl) ?: 'Tidak bisa memuat pratinjau', ENT_QUOTES, 'UTF-8') ?></pre>
                  </div>
                <?php endif; ?>
              </div>
              <div class="file-actions">
                <a href="<?= $downloadUrl ?>" class="btn btn-sm btn-primary">⬇️ Download</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
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
            <tr class="bsom-row" data-name="<?= htmlspecialchars($e['name'], ENT_QUOTES, 'UTF-8') ?>">
              <td class="bsom-name"><?= ($e['isDir'] ? '📁 ' : '📄 ') . $e['name'] ?></td>
              <td class="bsom-loc" style="font-size:13px;color:#64748b;"><?= ($e['rel'] !== '' ? $e['rel'] : ' — ') ?></td>
              <td>
                <?php if ($e['isDir']): ?>
                  <a href="<?= $e['link'] ?>" class="btn btn-sm btn-primary">Buka</a>
                <?php else: ?>
                  <a href="<?= $e['viewUrl'] ?>" class="btn btn-sm btn-primary">👁️ Lihat</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  <?php elseif (empty($entries)): ?>
    <p class="text-muted-custom">Tidak ada berkas atau folder ditemukan.</p>
  <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th style="width:42%;"><?= $headerLink('name', 'Nama') ?></th>
          <th style="width:24%;"><?= $headerLink('modified', 'Terakhir Dimodifikasi') ?></th>
          <th style="width:16%;"><?= $headerLink('size', 'Size') ?></th>
          <th style="width:18%;">Aksi</th>
        </tr>
      </thead>
      <tbody id="bsomTbody">
        <?php foreach ($entries as $e): ?>
          <tr class="bsom-row" data-name="<?= htmlspecialchars($e['name'], ENT_QUOTES, 'UTF-8') ?>">
            <td class="bsom-name">
              <?php if ($e['isDir'] && $e['isParent']): ?>
                <span class="icon">⬆️</span> <a href="<?= $e['link'] ?>" class="text-decoration-underline">Parent Directory</a>
              <?php elseif ($e['isDir']): ?>
                <span class="icon">📁</span> <a href="<?= $e['link'] ?>" class="text-decoration-underline"><?= $e['name'] ?></a>
              <?php else: ?>
                <span class="icon">📄</span> <?= $e['name'] ?>
              <?php endif; ?>
            </td>
            <td class="muted" style="font-size:13px;color:#64748b;"><?= ($e['modified'] !== '' ? $e['modified'] : '-') ?></td>
            <td class="muted" style="font-size:13px;color:#64748b;"><?= (!$e['isDir'] && $e['size'] !== '' ? $e['size'] : '-') ?></td>
            <td>
              <?php if ($e['isDir']): ?>
                <a href="<?= $e['link'] ?>" class="btn btn-sm btn-primary">Buka</a>
              <?php else: ?>
                <a href="<?= $e['viewUrl'] ?>" class="btn btn-sm btn-primary">👁️ Lihat</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<script>
  (function() {
    var search = document.getElementById('bsomSearch');
    var tbody = document.getElementById('bsomTbody');
    if (!search || !tbody) return;
    var apply = function() {
      var q = (search.value || '').toLowerCase();
      var inputVal = search.value || '';
      // In search mode, q is already in the input value; if input is editable, filter results too.
      if (q === '') {
        Array.from(tbody.querySelectorAll('tr.bsom-row')).forEach(function(tr) {
          tr.style.display = '';
        });
        return;
      }
      // Only filter if there is a separate filter intent (typing refines). Keep all matches.
    };
    search.addEventListener('input', function() {
      var q = (search.value || '').toLowerCase();
      if (q === '') {
        Array.from(tbody.querySelectorAll('tr.bsom-row')).forEach(function(tr) {
          tr.style.display = '';
        });
        return;
      }
      Array.from(tbody.querySelectorAll('tr.bsom-row')).forEach(function(tr) {
        var name = (tr.getAttribute('data-name') || '').toLowerCase();
        var loc = (tr.querySelector('.bsom-loc') ? tr.querySelector('.bsom-loc').textContent : '').toLowerCase();
        tr.style.display = (name.indexOf(q) !== -1 || loc.indexOf(q) !== -1) ? '' : 'none';
      });
    });
  })();
</script>