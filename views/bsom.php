<?php
/**
 * BSOM - Unified page: browse, search, and file preview
 */

$viewMode = isset($viewData);
$v = $viewMode ? $viewData : null;

// Helpers (browse mode only)
if (!$viewMode) {
    $sortUrl = function ($col, $dir) use ($pathEncoded) {
        $sep = ($pathEncoded !== '') ? '?path=' . $pathEncoded . '&' : '?';
        return BASE_URL . '/bsom' . $sep . 'sort=' . $col . '&dir=' . $dir;
    };

    $headerLink = function ($col, $label) use ($sortCol, $sortDir, $sortUrl) {
        $nextDir = ($sortCol === $col && $sortDir === 'asc') ? 'desc' : 'asc';
        $active = ($sortCol === $col) ? ' active' : '';
        $arrow = ($sortCol === $col) ? '<span class="sort-arrow">' . ($sortDir === 'asc' ? '↑' : '↓') . '</span>' : '';
        return '<a href="' . $sortUrl($col, $nextDir) . '" class="sortable' . $active . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . $arrow . '</a>';
    };
}

// Current file (view mode) / path context
if ($viewMode) {
    $file = $v['file'] ?? '';
    $crumbPath = ($file !== '') ? dirname($file) : '';
} else {
    $file = '';
    $crumbPath = $path ?? '';
}

// Breadcrumbs
$breadcrumbs = [];
if ($crumbPath !== '' && $crumbPath !== '.') {
    $acc = '';
    foreach (explode('/', $crumbPath) as $seg) {
        if ($seg === '') continue;
        $acc = $acc === '' ? $seg : $acc . '/' . $seg;
        $breadcrumbs[] = [
            'label' => $seg,
            'link' => BASE_URL . '/bsom?path=' . implode('/', array_map('rawurlencode', explode('/', $acc))),
        ];
    }
}

// Back link (view mode) -> folder containing the file
$backUrl = BASE_URL . '/bsom';
if ($viewMode && $file !== '') {
    $parent = dirname($file);
    if ($parent !== '' && $parent !== '.') {
        $backUrl .= '?path=' . implode('/', array_map('rawurlencode', explode('/', $parent)));
    }
}

// File helpers
$previewType = function ($name) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($ext === 'pdf') return 'pdf';
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'])) return 'image';
    if (in_array($ext, ['txt', 'log', 'csv', 'json', 'html', 'htm', 'css', 'js', 'xml', 'ini'])) return 'text';
    return 'none';
};

$iconFor = function ($type) {
    return $type === 'pdf' ? '📄' : ($type === 'image' ? '🖼️' : ($type === 'text' ? '📝' : '📄'));
};

$streamUrlFor = function ($rel) {
    $enc = implode('/', array_map('rawurlencode', explode('/', $rel)));
    return BASE_URL . '/actions/bsom-stream.php?file=' . $enc;
};

// Action buttons for a file (inline preview when previewable, otherwise full page)
$fileActions = function ($name, $viewUrl, $downloadUrl, $rel) use ($previewType, $streamUrlFor) {
    $type = $previewType($name);
    $out = '<div class="bsom-actions">';
    if ($type !== 'none') {
        $out .= '<a href="' . htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') . '" class="btn-modern btn-modern-primary btn-modern-sm js-preview" data-type="' . $type . '" data-stream="' . htmlspecialchars($streamUrlFor($rel), ENT_QUOTES, 'UTF-8') . '">Lihat</a>';
    } else {
        $out .= '<a href="' . htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') . '" class="btn-modern btn-modern-primary btn-modern-sm">Lihat</a>';
    }
    if (!empty($downloadUrl)) {
        $out .= '<a href="' . htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') . '" class="btn-modern btn-modern-ghost btn-modern-sm">Unduh</a>';
    }
    $out .= '</div>';
    return $out;
};
?>

<style>
    .bsom-wrapper {
        width: 100%;
        padding: 4px 0 40px;
    }

    /* Header */
    .bsom-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }

    .bsom-page-title {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 6px;
        letter-spacing: -0.02em;
        word-break: break-word;
    }

    .bsom-page-subtitle {
        font-size: 14px;
        color: #64748b;
        margin: 0;
    }

    /* Breadcrumbs */
    .bsom-breadcrumbs {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 4px;
        margin-bottom: 16px;
        font-size: 13px;
    }

    .bsom-breadcrumbs a {
        color: #3b82f6;
        text-decoration: none;
        padding: 3px 8px;
        border-radius: 6px;
        transition: all 0.15s ease;
    }

    .bsom-breadcrumbs a:hover {
        background: #eff6ff;
        color: #2563eb;
    }

    .bsom-breadcrumbs .separator {
        color: #cbd5e1;
        user-select: none;
    }

    .bsom-breadcrumbs .current {
        color: #0f172a;
        font-weight: 600;
        padding: 3px 8px;
    }

    .bsom-breadcrumbs .home {
        color: #334155;
    }

    /* Search */
    .bsom-search-form {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .bsom-search-input {
        flex: 1;
        min-width: 240px;
        max-width: 420px;
        padding: 10px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        color: #0f172a;
        background: #fff;
        transition: all 0.15s ease;
    }

    .bsom-search-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .bsom-search-input::placeholder {
        color: #94a3b8;
    }

    .btn-modern {
        padding: 10px 18px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }

    .btn-modern-primary {
        background: #3b82f6;
        color: #fff;
    }

    .btn-modern-primary:hover {
        background: #2563eb;
    }

    .btn-modern-success {
        background: #10b981;
        color: #fff;
    }

    .btn-modern-success:hover {
        background: #059669;
    }

    .btn-modern-ghost {
        background: transparent;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    .btn-modern-ghost:hover {
        background: #f8fafc;
        color: #334155;
    }

    .btn-modern-sm {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 8px;
    }

    /* Stats */
    .bsom-stats {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 16px;
    }

    .bsom-stats strong {
        color: #0f172a;
    }

    .bsom-stats .warning {
        color: #f59e0b;
    }

    /* Card */
    .bsom-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
    }

    /* Table */
    .bsom-table {
        width: 100%;
        border-collapse: collapse;
    }

    .bsom-table th {
        padding: 10px 20px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
        white-space: nowrap;
    }

    .bsom-table td {
        padding: 12px 20px;
        font-size: 14px;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }

    .bsom-table tbody tr:last-child td {
        border-bottom: none;
    }

    .bsom-table tbody tr.bsom-row {
        transition: background 0.1s ease;
    }

    .bsom-table tbody tr.bsom-row:hover {
        background: #f8fafc;
    }

    .bsom-detail-row td {
        padding: 0 20px 14px;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
    }

    .sortable {
        color: inherit;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }

    .sortable:hover {
        color: #0f172a;
    }

    .sortable.active {
        color: #3b82f6;
    }

    .sort-arrow {
        font-size: 11px;
    }

    /* File row */
    .bsom-file-cell {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .bsom-icon {
        font-size: 18px;
        flex-shrink: 0;
        line-height: 1;
    }

    .bsom-name-link {
        color: #0f172a;
        text-decoration: none;
        font-weight: 500;
        word-break: break-word;
    }

    .bsom-name-link:hover {
        color: #3b82f6;
    }

    .bsom-file-name {
        color: #0f172a;
        word-break: break-word;
        font-weight: 500;
        font-size: 14px;
    }

    .bsom-meta {
        color: #94a3b8;
        font-size: 13px;
    }

    /* Actions */
    .bsom-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    /* Grouped results */
    .bsom-group {
        margin-bottom: 16px;
    }

    .bsom-group-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px 10px 0 0;
        font-weight: 600;
        font-size: 13px;
        color: #334155;
    }

    .bsom-group-header .count {
        font-weight: 400;
        color: #94a3b8;
        font-size: 12px;
    }

    .bsom-group .bsom-card {
        border-radius: 0 0 10px 10px;
        border-top: none;
    }

    .bsom-file-block {
        padding: 14px 20px;
        border-bottom: 1px solid #f1f5f9;
    }

    .bsom-file-block:last-child {
        border-bottom: none;
    }

    .bsom-file-block-head {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        flex-wrap: wrap;
    }

    .bsom-file-info {
        flex: 1;
        min-width: 0;
    }

    /* Preview (shared by inline listing previews and file view) */
    .bsom-preview {
        margin-top: 10px;
        padding: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }

    .bsom-preview iframe {
        width: 100%;
        min-height: 400px;
        border: none;
        border-radius: 6px;
        background: #fff;
    }

    .bsom-preview img {
        width: 100%;
        max-height: 320px;
        border-radius: 6px;
        object-fit: contain;
        background: #fff;
    }

    .bsom-preview pre {
        max-height: 240px;
        overflow: auto;
        font-size: 12px;
        margin: 0;
        background: #fff;
        padding: 10px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        line-height: 1.5;
    }

    /* File view mode */
    .bsom-view-preview {
        padding: 20px;
        min-height: 160px;
        overflow: auto;
    }

    .bsom-view-preview pre {
        margin: 0;
        font-size: 13px;
        line-height: 1.6;
        color: #334155;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .bsom-view-preview table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .bsom-view-preview table th,
    .bsom-view-preview table td {
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        text-align: left;
    }

    .bsom-view-preview table th {
        background: #f8fafc;
        font-weight: 600;
    }

    .bsom-view-preview img {
        max-width: 100%;
        height: auto;
        border-radius: 10px;
    }

    .bsom-view-preview iframe {
        width: 100%;
        height: 520px;
        border: none;
        border-radius: 10px;
    }

    .bsom-view-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        padding: 16px 20px;
        border-top: 1px solid #f1f5f9;
        background: #f8fafc;
    }

    /* Empty / Error */
    .bsom-empty {
        padding: 48px 20px;
        text-align: center;
        color: #94a3b8;
        font-size: 14px;
    }

    .bsom-view-fallback {
        padding: 32px;
        text-align: center;
        color: #64748b;
        font-size: 14px;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
    }

    .bsom-error {
        padding: 14px 16px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 10px;
        color: #991b1b;
        margin-bottom: 20px;
        font-size: 14px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .bsom-page-title {
            font-size: 20px;
        }

        .bsom-table th:nth-child(2),
        .bsom-table td:nth-child(2) {
            display: none;
        }
    }
</style>

<div class="bsom-wrapper">
    <!-- Page Header -->
    <div class="bsom-page-header">
        <div>
            <h1 class="bsom-page-title"><?= $viewMode ? htmlspecialchars($v['name'] !== '' ? $v['name'] : 'Lihat Berkas', ENT_QUOTES, 'UTF-8') : 'BSOM Files' ?></h1>
            <p class="bsom-page-subtitle"><?= $viewMode ? 'Pratinjau berkas dari server BSOM' : 'Telusuri dan unduh berkas dari server BSOM' ?></p>
        </div>
        <?php if (!$viewMode): ?>
            <a href="<?= BASE_URL ?>/bsom" class="btn-modern btn-modern-ghost btn-modern-sm">↲ Akar</a>
        <?php endif; ?>
    </div>

    <!-- Breadcrumbs -->
    <?php if (!empty($breadcrumbs)): ?>
        <nav class="bsom-breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= BASE_URL ?>/bsom" class="home">⌂ Root</a>
            <?php foreach ($breadcrumbs as $i => $crumb): ?>
                <span class="separator">/</span>
                <?php if (!$viewMode && $i === count($breadcrumbs) - 1): ?>
                    <span class="current"><?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php else: ?>
                    <a href="<?= $crumb['link'] ?>"><?= htmlspecialchars($crumb['label'], ENT_QUOTES, 'UTF-8') ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <!-- Search (available in both modes) -->
    <form method="get" action="<?= BASE_URL ?>/bsom" class="bsom-search-form">
        <?php if (!$viewMode && ($pathEncoded ?? '') !== ''): ?>
            <input type="hidden" name="path" value="<?= htmlspecialchars($pathEncoded, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        <input type="text" id="bsomSearch" name="q" class="bsom-search-input" value="<?= htmlspecialchars($query ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Cari berkas atau folder...">
        <button type="submit" class="btn-modern btn-modern-primary">Cari</button>
        <?php if (($query ?? '') !== ''): ?>
            <a href="<?= BASE_URL ?>/bsom<?= ($pathEncoded ?? '') !== '' ? '?path=' . $pathEncoded : '' ?>" class="btn-modern btn-modern-ghost">Bersihkan</a>
        <?php endif; ?>
    </form>

<?php if ($viewMode): ?>
    <!-- ============ VIEW MODE ============ -->
    <?php if ($v['error'] !== null): ?>
        <div class="bsom-error"><?= $v['error'] ?></div>
    <?php else: ?>
        <div class="bsom-stats">
            <?= $v['size'] !== '' ? htmlspecialchars($v['size'], ENT_QUOTES, 'UTF-8') : '-' ?>
            <?php if ($v['mime'] !== ''): ?> · <?= htmlspecialchars($v['mime'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
            <?php if ($v['modified'] !== ''): ?> · <?= htmlspecialchars($v['modified'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
        </div>
        <div class="bsom-card">
            <div class="bsom-view-preview">
                <?php if ($v['viewType'] === 'text'): ?>
                    <pre><?= htmlspecialchars($v['preview'], ENT_QUOTES, 'UTF-8') ?></pre>
                <?php elseif ($v['viewType'] === 'csv'): ?>
                    <table>
                        <?php
                        $rows = preg_split('/\r\n|\r|\n/', trim($v['preview']));
                        $i = 0;
                        foreach ($rows as $r) {
                            if (trim($r) === '') continue;
                            $cells = str_get_csv($r);
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
                    <img src="<?= $v['streamUrl'] ?>" alt="<?= htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8') ?>">
                <?php elseif ($v['viewType'] === 'pdf'): ?>
                    <iframe src="<?= $v['streamUrl'] ?>" type="application/pdf"></iframe>
                <?php else: ?>
                    <div class="bsom-view-fallback">Berkas ini tidak dapat ditampilkan langsung di browser. Silakan unduh untuk membukanya.</div>
                <?php endif; ?>
            </div>
            <div class="bsom-view-actions">
                <?php if (!empty($v['downloadUrl'])): ?>
                    <a href="<?= $v['downloadUrl'] ?>" class="btn-modern btn-modern-success">⬇ Unduh Berkas</a>
                <?php endif; ?>
                <a href="<?= $backUrl ?>" class="btn-modern btn-modern-ghost">← Kembali</a>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- ============ BROWSE / SEARCH MODE ============ -->

    <!-- Stats -->
    <?php if (($query ?? '') !== '' && $searchTotal > 0 && !empty($searchStats)): ?>
        <div class="bsom-stats">
            <strong><?= $searchTotal ?></strong> hasil untuk "<strong><?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?></strong>"
            <?php if (!empty($searchStats['partial'])): ?>
                <span class="warning">· <?= htmlspecialchars($searchError ?? 'hasil parsial', ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Error -->
    <?php if ($error !== null): ?>
        <div class="bsom-error"><?= $error ?></div>
    <?php endif; ?>

    <!-- Content -->
    <?php if (($query ?? '') !== '' && $searchTotal === 0 && $error === null): ?>
        <div class="bsom-card">
            <div class="bsom-empty">Tidak ada hasil yang cocok. Coba kata kunci lain.</div>
        </div>

    <?php elseif (($query ?? '') !== '' && $searchTotal > 0): ?>
        <?php if ($autoGrouped && !empty($groupedResults)): ?>
            <!-- Grouped Search Results -->
            <?php foreach ($groupedResults as $season => $files): ?>
                <div class="bsom-group">
                    <div class="bsom-group-header">
                        <?= htmlspecialchars($season, ENT_QUOTES, 'UTF-8') ?>
                        <span class="count"><?= count($files) ?> file</span>
                    </div>
                    <div class="bsom-card">
                        <?php foreach ($files as $f):
                            $type = $previewType($f['name']);
                            $encRel = implode('/', array_map('rawurlencode', explode('/', $f['rel'])));
                            $viewUrl = BASE_URL . '/bsom/view?file=' . $encRel;
                            $streamUrl = BASE_URL . '/actions/bsom-stream.php?file=' . $encRel;
                            $downloadUrl = BASE_URL . '/actions/bsom-download.php?file=' . $encRel;
                        ?>
                            <div class="bsom-file-block">
                                <div class="bsom-file-block-head">
                                    <span class="bsom-icon"><?= $iconFor($type) ?></span>
                                    <div class="bsom-file-info">
                                        <div class="bsom-file-name"><?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="bsom-meta"><?= htmlspecialchars($f['rel'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if ($f['modified'] !== ''): ?>
                                            <div class="bsom-meta"><?= htmlspecialchars($f['modified'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?= $fileActions($f['name'], $viewUrl, $downloadUrl, $f['rel']) ?>
                                </div>
                                <div class="bsom-preview" hidden></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <!-- Search Results Table -->
            <div class="bsom-card">
                <table class="bsom-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Lokasi</th>
                            <th>Update</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="bsomTbody">
                        <?php foreach ($searchResults as $e): ?>
                            <tr class="bsom-row" data-name="<?= htmlspecialchars($e['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <td>
                                    <div class="bsom-file-cell">
                                        <span class="bsom-icon"><?= ($e['isDir'] ? '📁' : '📄') ?></span>
                                        <span class="bsom-file-name"><?= htmlspecialchars($e['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </td>
                                <td class="bsom-loc bsom-meta"><?= $e['rel'] !== '' ? htmlspecialchars($e['rel'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                <td class="bsom-meta"><?= $e['modified'] !== '' ? htmlspecialchars($e['modified'], ENT_QUOTES, 'UTF-8') : '-' ?></td>
                                <td>
                                    <?php if ($e['isDir']): ?>
                                        <div class="bsom-actions">
                                            <a href="<?= $e['link'] ?>" class="btn-modern btn-modern-primary btn-modern-sm">Buka</a>
                                        </div>
                                    <?php else: ?>
                                        <?= $fileActions($e['name'], $e['viewUrl'], $e['downloadUrl'], $e['rel']) ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if (!$e['isDir']): ?>
                                <tr class="bsom-detail-row" hidden><td colspan="4"><div class="bsom-preview"></div></td></tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php elseif (empty($entries)): ?>
        <div class="bsom-card">
            <div class="bsom-empty">Tidak ada berkas atau folder ditemukan.</div>
        </div>

    <?php else: ?>
        <!-- Directory Listing -->
        <div class="bsom-card">
            <table class="bsom-table">
                <thead>
                    <tr>
                        <th><?= $headerLink('name', 'Nama') ?></th>
                        <th><?= $headerLink('modified', 'Dimodifikasi') ?></th>
                        <th><?= $headerLink('size', 'Ukuran') ?></th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="bsomTbody">
                    <?php foreach ($entries as $e): ?>
                        <tr class="bsom-row" data-name="<?= htmlspecialchars($e['name'], ENT_QUOTES, 'UTF-8') ?>">
                            <td>
                                <div class="bsom-file-cell">
                                    <span class="bsom-icon"><?= ($e['isDir'] ? '📁' : '📄') ?></span>
                                    <?php if ($e['isDir']): ?>
                                        <a href="<?= $e['link'] ?>" class="bsom-name-link"><?= htmlspecialchars($e['name'], ENT_QUOTES, 'UTF-8') ?></a>
                                    <?php else: ?>
                                        <span class="bsom-file-name"><?= htmlspecialchars($e['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="bsom-meta"><?= ($e['modified'] !== '' ? $e['modified'] : '-') ?></td>
                            <td class="bsom-meta"><?= (!$e['isDir'] && $e['size'] !== '' ? $e['size'] : '-') ?></td>
                            <td>
                                <?php if ($e['isDir']): ?>
                                    <div class="bsom-actions">
                                        <a href="<?= $e['link'] ?>" class="btn-modern btn-modern-primary btn-modern-sm">Buka</a>
                                    </div>
                                <?php else: ?>
                                    <?= $fileActions($e['name'], $e['viewUrl'], $e['downloadUrl'], $e['rel']) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!$e['isDir']): ?>
                            <tr class="bsom-detail-row" hidden><td colspan="4"><div class="bsom-preview"></div></td></tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php endif; ?>
</div>

<script>
(function() {
    // Inline preview toggle (search results + directory listing)
    function findPreviewBox(btn) {
        var tr = btn.closest('tr.bsom-row');
        if (tr) {
            var next = tr.nextElementSibling;
            if (next && next.classList.contains('bsom-detail-row')) {
                return next.querySelector('.bsom-preview');
            }
        }
        var block = btn.closest('.bsom-file-block');
        if (block) return block.querySelector('.bsom-preview');
        return null;
    }

    document.querySelectorAll('.js-preview').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var box = findPreviewBox(btn);
            if (!box) return;

            if (!box.hasAttribute('hidden')) {
                box.setAttribute('hidden', '');
                btn.textContent = 'Lihat';
                return;
            }

            box.removeAttribute('hidden');
            btn.textContent = 'Tutup';

            if (box.dataset.loaded === '1') return;
            box.dataset.loaded = '1';

            var type = btn.getAttribute('data-type') || 'none';
            var stream = btn.getAttribute('data-stream') || '';

            if (type === 'pdf') {
                box.innerHTML = '<iframe src="' + stream + '" type="application/pdf"></iframe>';
            } else if (type === 'image') {
                box.innerHTML = '<img src="' + stream + '" alt="">';
            } else if (type === 'text') {
                box.innerHTML = '<pre>Memuat…</pre>';
                fetch(stream).then(function(r) { return r.text(); }).then(function(t) {
                    var pre = box.querySelector('pre');
                    if (pre) pre.textContent = t || '(berkas kosong)';
                }).catch(function() {
                    var pre = box.querySelector('pre');
                    if (pre) pre.textContent = 'Gagal memuat pratinjau.';
                });
            }
        });
    });

    // Client-side row filter (refines current table only)
    var search = document.getElementById('bsomSearch');
    var tbody = document.getElementById('bsomTbody');
    if (search && tbody) {
        search.addEventListener('input', function() {
            var q = (search.value || '').toLowerCase().trim();
            var rows = tbody.querySelectorAll('tr.bsom-row');

            if (q === '') {
                rows.forEach(function(tr) {
                    tr.style.display = '';
                    var next = tr.nextElementSibling;
                    if (next && next.classList.contains('bsom-detail-row')) next.style.display = '';
                });
                return;
            }

            rows.forEach(function(tr) {
                var name = (tr.getAttribute('data-name') || '').toLowerCase();
                var locEl = tr.querySelector('.bsom-loc');
                var loc = locEl ? locEl.textContent.toLowerCase() : '';
                var visible = (name.indexOf(q) !== -1 || loc.indexOf(q) !== -1);
                tr.style.display = visible ? '' : 'none';
                var next = tr.nextElementSibling;
                if (next && next.classList.contains('bsom-detail-row')) next.style.display = visible ? '' : 'none';
            });
        });
    }
})();
</script>
