<div class="hero-section">
  <h1>QR Code PC</h1>
  <p>Generate QR code untuk data Printer, PC, dan IP. </p>
</div>

<?php if (empty($qrResults)): ?>
  <div class="card">
    <div class="card-header">📝 Input Data</div>
    <form method="POST" action="/newqr/buat-qr" id="qrForm">
      <div id="rowsContainer">
        <?php foreach ($rows as $idx => $row): ?>
          <div class="qr-row qr-card" data-index="<?= htmlspecialchars($idx) ?>">
            <div class="qr-card-header">
              <h4>Data #<?= $idx + 1 ?></h4>
              <?php if (count($rows) > 1): ?>
                <button type="button" class="btn btn-danger btn-sm remove-row">Hapus</button>
              <?php endif; ?>
            </div>
            <div class="row">
              <div class="col-sm-3">
                <div class="form-group">
                  <label>QR</label>
                  <input type="text" name="rows[nama][]" class="form-control" placeholder="Contoh: Printer-Lobby" value="<?= htmlspecialchars($row['nama']) ?>">
                </div>
              </div>
              <div class="col-sm-2">
                <div class="form-group">
                  <label>Printer</label>
                  <input type="text" name="rows[printer][]" class="form-control" placeholder="HP LaserJet" value="<?= htmlspecialchars($row['printer']) ?>">
                </div>
              </div>
              <div class="col-sm-2">
                <div class="form-group">
                  <label>PC</label>
                  <input type="text" name="rows[pc][]" class="form-control" placeholder="PC-01" value="<?= htmlspecialchars($row['pc']) ?>">
                </div>
              </div>
              <div class="col-sm-2">
                <div class="form-group">
                  <label>IP PC</label>
                  <input type="text" name="rows[ip_pc][]" class="form-control" placeholder="192.168.1.10" value="<?= htmlspecialchars($row['ip_pc']) ?>">
                </div>
              </div>
              <div class="col-sm-2">
                <div class="form-group">
                  <label>IP Printer</label>
                  <input type="text" name="rows[ip_printer][]" class="form-control" placeholder="192.168.1.20" value="<?= htmlspecialchars($row['ip_printer']) ?>">
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div style="margin-bottom:15px;">
        <button type="button" class="btn btn-success" id="addRowBtn">+ Tambah Baris</button>
        <span style="margin-left:15px;color:#6b7280;font-size:14px;">Maksimal 51 baris (A4)</span>
      </div>

      <div class="form-group" style="margin-top:20px;">
        <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;font-size:16px;">🚀 Generate QR Code</button>
      </div>
    </form>
  </div>

  <script>
    (function() {
      var maxRows = 51;
      var container = document.getElementById('rowsContainer');
      var addBtn = document.getElementById('addRowBtn');

      function updateRowNumbers() {
        var rows = container.querySelectorAll('.qr-row');
        rows.forEach(function(row, idx) {
          var title = row.querySelector('h4');
          if (title) title.textContent = 'Data #' + (idx + 1);
          row.setAttribute('data-index', idx);
        });
      }

      addBtn.addEventListener('click', function() {
        var rows = container.querySelectorAll('.qr-row');
        if (rows.length >= maxRows) {
          alert('Maksimal ' + maxRows + ' baris');
          return;
        }

        var div = document.createElement('div');
        div.className = 'qr-row';
        div.setAttribute('data-index', rows.length);
        div.style.cssText = 'border:1px solid #e5e7eb;border-radius:8px;padding:15px;margin-bottom:15px;background:#f9fafb;';
        div.innerHTML = '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">' +
          '<h4 style="margin:0;color:#374151;">Data #' + (rows.length + 1) + '</h4>' +
          '<button type="button" class="btn btn-danger btn-sm remove-row">Hapus</button>' +
          '</div>' +
          '<div class="row">' +
          '<div class="col-sm-3"><div class="form-group"><label>QR</label><input type="text" name="rows[nama][]" class="form-control" placeholder="Contoh: Printer-Lobby"></div></div>' +
          '<div class="col-sm-2"><div class="form-group"><label>Printer</label><input type="text" name="rows[printer][]" class="form-control" placeholder="HP LaserJet"></div></div>' +
          '<div class="col-sm-2"><div class="form-group"><label>PC</label><input type="text" name="rows[pc][]" class="form-control" placeholder="PC-01"></div></div>' +
          '<div class="col-sm-2"><div class="form-group"><label>IP PC</label><input type="text" name="rows[ip_pc][]" class="form-control" placeholder="192.168.1.10"></div></div>' +
          '<div class="col-sm-2"><div class="form-group"><label>IP Printer</label><input type="text" name="rows[ip_printer][]" class="form-control" placeholder="192.168.1.20"></div></div>' +
          '</div>';

        container.appendChild(div);
        attachRemove(div);
      });

      function attachRemove(row) {
        var btn = row.querySelector('.remove-row');
        if (!btn) return;
        btn.addEventListener('click', function() {
          var rows = container.querySelectorAll('.qr-row');
          if (rows.length <= 1) {
            alert('Minimal 1 baris');
            return;
          }
          row.parentNode.removeChild(row);
          updateRowNumbers();
        });
      }

      container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
          var row = e.target.closest('.qr-row');
          if (!row) return;
          var rows = container.querySelectorAll('.qr-row');
          if (rows.length <= 1) {
            alert('Minimal 1 baris');
            return;
          }
          row.parentNode.removeChild(row);
          updateRowNumbers();
        }
      });
    })();
  </script>

<?php else: ?>
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">✅ Hasil QR Code (<?= count($qrResults) ?> data)</div>
    <div style="padding:20px;">
      <div class="row">
        <?php foreach ($qrResults as $idx => $item): ?>
          <div class="col-sm-6 col-md-4" style="margin-bottom:20px;">
            <div style="border:1px solid #e5e7eb;border-radius:8px;padding:12px;background:#fff;overflow:hidden;">
              <div style="float:left;width:90px;text-align:center;margin-right:12px;">
                <img src="<?= htmlspecialchars($item['qr_image']) ?>" alt="QR Code" style="border:1px solid #ddd;padding:4px;background:#fff;width:80px;height:80px;object-fit:contain;">
              </div>
              <div style="overflow:hidden;font-size:12px;color:#374151;line-height:1.5;">
                <div><strong>Printer:</strong> <?= htmlspecialchars($item['printer']) ?></div>
                <div><strong>PC:</strong> <?= htmlspecialchars($item['pc']) ?></div>
                <div><strong>IP PC:</strong> <?= htmlspecialchars($item['ip_pc']) ?></div>
                <div><strong>IP Printer:</strong> <?= htmlspecialchars($item['ip_printer']) ?></div>
                <div style="margin-top:8px;">
                  <a href="<?= htmlspecialchars($item['qr_image']) ?>" download class="btn btn-success btn-sm">⬇️ Download</a>
                  <button type="button" class="btn btn-primary btn-sm" onclick='printQr("<?= htmlspecialchars($item['qr_image']) ?>", "<?= htmlspecialchars($item['nama']) ?>", "<?= htmlspecialchars($item['printer']) ?>", "<?= htmlspecialchars($item['pc']) ?>", "<?= htmlspecialchars($item['ip_pc']) ?>", "<?= htmlspecialchars($item['ip_printer']) ?>")'>🖨️ Print</button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div style="margin-top:20px;text-align:center;">
        <a href="/newqr/buat-qr" class="btn btn-default">🔄 Buat Baru</a>
        <button type="button" class="btn btn-primary" onclick="printAll()">🖨️ Print Semua</button>
      </div>
    </div>
  </div>

  <script>
    function printQr(imgSrc, title, printer, pc, ipPc, ipPrinter) {
      var win = window.open('', '_blank', 'width=900,height=600');
      win.document.write('<!DOCTYPE html><html><head><title>Print QR</title>');
      win.document.write('<style>');
      win.document.write('@page { size: auto; margin: 0mm; }');
      win.document.write('body { margin: 0; padding: 0; font-family: Arial, sans-serif; }');
      win.document.write('.label { width: 5cm; height: 1.6cm; border: 1px solid #000; padding: 1mm; margin: 0 2mm 0 0; display: inline-block; vertical-align: top; box-sizing: border-box; page-break-inside: avoid; }');
      win.document.write('.label table { width: 100%; height: 100%; border-collapse: collapse; table-layout: fixed; }');
      win.document.write('.label td { padding: 0; vertical-align: middle; }');
      win.document.write('.qr { width: 1.7cm; text-align: center; }');
      win.document.write('.qr img { width: 1.3cm; height: 1.3cm; display: block; margin: 0 auto; }');
      win.document.write('.title { width: 1.3cm; font-size: 6pt; font-weight: 700; text-align: left; line-height: 1.3; }');
      win.document.write('.info { font-size: 6pt; line-height: 1.3; color: #000; }');
      win.document.write('.info .row { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }');
      win.document.write('.info strong { font-weight: 700; }');
      win.document.write('</style>');
      win.document.write('</head><body>');
      win.document.write('<div class="label">');
      win.document.write('  <table>');
      win.document.write('    <tr>');
      win.document.write('      <td class="qr"><img src="' + imgSrc + '"></td>');
      win.document.write('      <td class="title">');
      win.document.write('        <div>Printer</div>');
      win.document.write('        <div>PC</div>');
      win.document.write('        <div>IP PC</div>');
      win.document.write('        <div>IP Printer</div>');
      win.document.write('      </td>');
      win.document.write('      <td class="info">');
      win.document.write('        <div class="row">' + printer + '</div>');
      win.document.write('        <div class="row">' + pc + '</div>');
      win.document.write('        <div class="row">' + ipPc + '</div>');
      win.document.write('        <div class="row">' + ipPrinter + '</div>');
      win.document.write('      </td>');
      win.document.write('    </tr>');
      win.document.write('  </table>');
      win.document.write('</div>');
      win.document.write('<scr' + 'ipt>window.onload=function(){window.print();}</scr' + 'ipt>');
      win.document.write('</body></html>');
      win.document.close();
    }

    function printAll() {
      var win = window.open('', '_blank', 'width=900,height=600');
      win.document.write('<!DOCTYPE html><html><head><title>Print Semua QR</title>');
      win.document.write('<style>');
      win.document.write('@page { size: auto; margin: 0mm; }');
      win.document.write('body { margin: 0; padding: 0; font-family: Arial, sans-serif; }');
      win.document.write('.label-row { white-space: nowrap; margin-bottom: 0; }');
      win.document.write('.label { width: 5cm; height: 1.6cm; border: 1px solid #000; padding: 1mm; margin: 0 2mm 0 0; display: inline-block; vertical-align: top; box-sizing: border-box; page-break-inside: avoid; }');
      win.document.write('.label table { width: 100%; height: 100%; border-collapse: collapse; table-layout: fixed; }');
      win.document.write('.label td { padding: 0; vertical-align: middle; }');
      win.document.write('.qr { width: 1.7cm; text-align: center; }');
      win.document.write('.qr img { width: 1.3cm; height: 1.3cm; display: block; margin: 0 auto; }');
      win.document.write('.title { width: 1.3cm; font-size: 6pt; font-weight: 700; text-align: left; line-height: 1.3; }');
      win.document.write('.info { font-size: 6pt; line-height: 1.3; color: #000; }');
      win.document.write('.info .row { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }');
      win.document.write('.info strong { font-weight: 700; }');
      win.document.write('</style>');
      win.document.write('</head><body>');

      <?php foreach ($qrResults as $item): ?>
        win.document.write('<div class="label">');
        win.document.write('  <table>');
        win.document.write('    <tr>');
        win.document.write('      <td class="qr"><img src="<?= htmlspecialchars($item['qr_image']) ?>"></td>');
        win.document.write('      <td class="title">');
        win.document.write('        <div>Printer</div>');
        win.document.write('        <div>PC</div>');
        win.document.write('        <div>IP PC</div>');
        win.document.write('        <div>IP Printer</div>');
        win.document.write('      </td>');
        win.document.write('      <td class="info">');
        win.document.write('        <div class="row"><?= htmlspecialchars($item['printer']) ?></div>');
        win.document.write('        <div class="row"><?= htmlspecialchars($item['pc']) ?></div>');
        win.document.write('        <div class="row"><?= htmlspecialchars($item['ip_pc']) ?></div>');
        win.document.write('        <div class="row"><?= htmlspecialchars($item['ip_printer']) ?></div>');
        win.document.write('      </td>');
        win.document.write('    </tr>');
        win.document.write('  </table>');
        win.document.write('</div>');
      <?php endforeach; ?>

      win.document.write('<scr' + 'ipt>window.onload=function(){window.print();}</scr' + 'ipt>');
      win.document.write('</body></html>');
      win.document.close();
    }
  </script>

<?php endif; ?>