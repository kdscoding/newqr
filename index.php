<?php
require_once __DIR__ . '/app/Config.php';
require_once __DIR__ . '/app/Services/AuthService.php';
require_once __DIR__ . '/app/Services/CleanupService.php';
require_once __DIR__ . '/excel_reader.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

AuthService::check();

CleanupService::run();

$db = Config::db();

$qrDir = __DIR__ . "/QR/";
if (is_dir($qrDir)) {
    $now = time();
    $maxAge = 86400;
    foreach (glob($qrDir . "*.png") as $file) {
        if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
            @unlink($file);
        }
    }
}

$page = 'home';
$version = '';
$dataType = '';
$printVariant = 'zigzag';

if (isset($_GET['v'])) {
    $allowedPages = ['home', 'dt', 'im', 'qr', 'print', 'print2', 'login'];
    $page = in_array($_GET['v'], $allowedPages, true) ? $_GET['v'] : 'home';
    $version = $_GET['version'] ?? '';
    $allowedData = ['inhouse', 'sbsite', 'paxar', 'supplier', 'tl', 'additional_label'];
    $dataType = isset($_GET['data']) && in_array($_GET['data'], $allowedData, true) ? $_GET['data'] : '';
} else {
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $route = substr($requestUri, strlen($scriptDir));
    $route = trim($route, '/');

    if ($route === '' || $route === 'home') {
        $page = 'home';
    } elseif ($route === 'data-qr') {
        $page = 'dt';
    } elseif ($route === 'import') {
        $page = 'im';
    } elseif ($route === 'buat-qr') {
        $page = 'qr';
    } elseif (preg_match('#^print/([^/]+)/([^/]+)(?:_left)?$#', $route, $m)) {
        $page = 'print';
        $version = $m[1];
        $dataType = $m[2];
        $printVariant = str_ends_with($route, '_left') ? 'left' : 'zigzag';
        if (str_ends_with($dataType, '_left')) {
            $dataType = substr($dataType, 0, -5);
        }
    } elseif (preg_match('#^print-xerox/([^/]+)/([^/]+)$#', $route, $m)) {
        $page = 'print2';
        $version = $m[1];
        $dataType = $m[2];
    } elseif (preg_match('#^trigger-label/([^/]+)/([^/]+)$#', $route, $m)) {
        $page = 'print';
        $version = $m[1];
        $dataType = $m[2];
    } elseif ($route === 'login') {
        $page = 'login';
    }
}
?>

<?php if ($page === 'login'): ?>
  <?php require_once BASE_PATH . '/views/login.php'; ?>
<?php else: ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>HWASEUNG - QR Label Printing System</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="<?=BASE_URL?>/assets/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>

<body>
  <?php
  $loadingConfig = null;

  if ($page == 'home') {
      $loadingConfig = $loadingConfig['dashboard'] ?? [
          'theme' => 'theme-system',
          'title' => 'HWASEUNG',
          'subtitle' => 'SYSTEM INITIALIZING',
          'footer' => 'HWASEUNG INDONESIA // DASHBOARD MODULE',
          'pageType' => 'home',
          'messages' => [
              ['text' => '> HWASEUNG: Loading dashboard modules...', 'type' => 'command'],
              ['text' => '> HWASEUNG: Fetching statistics...', 'type' => 'command'],
              ['text' => '> HWASEUNG: Syncing with database...', 'type' => 'command'],
              ['text' => '> HWASEUNG: Rendering interface...', 'type' => 'command'],
              ['text' => '> HWASEUNG: System ready!', 'type' => 'success']
          ],
          'duration' => 3500
      ];
  } elseif ($page == 'im' && $_SERVER['REQUEST_METHOD'] === 'POST') {
      $loadingConfig = $loadingConfig['import'] ?? [
          'theme' => 'theme-import',
          'title' => 'HWASEUNG',
          'subtitle' => 'DATA IMPORT MODULE',
          'footer' => 'HWASEUNG INDONESIA // EXCEL PARSER v2.0',
          'pageType' => 'import',
          'messages' => [
              ['text' => '> HWASEUNG: Scanning file system...', 'type' => 'command'],
              ['text' => '> HWASEUNG: Detecting Excel format...', 'type' => 'command'],
              ['text' => '> HWASEUNG: Parsing data structure...', 'type' => 'command'],
              ['text' => '> HWASEUNG: Validating integrity...', 'type' => 'warning'],
              ['text' => '> HWASEUNG: Importing records...', 'type' => 'command'],
              ['text' => '> HWASEUNG: Generating QR codes...', 'type' => 'command'],
              ['text' => '> HWASEUNG: Database synchronized!', 'type' => 'success']
          ],
          'duration' => 4000
      ];
  } elseif ($page == 'qr' && $_SERVER['REQUEST_METHOD'] === 'POST') {
      $loadingConfig = $loadingConfig['upload'] ?? [
          'theme' => 'theme-hack',
          'title' => 'HWASEUNG',
          'subtitle' => 'QR GENERATION',
          'footer' => 'HWASEUNG INDONESIA // PROCESSING',
          'pageType' => 'upload',
          'messages' => [
              ['text' => '> HWASEUNG: Validating input data...', 'type' => 'command'],
              ['text' => '> HWASEUNG: Generating QR matrix...', 'type' => 'command'],
              ['text' => '> HWASEUNG: Encoding payload...', 'type' => 'command'],
              ['text' => '> HWASEUNG: Rendering QR codes...', 'type' => 'command'],
              ['text' => '> HWASEUNG: Process complete!', 'type' => 'success']
          ],
          'duration' => 3000
      ];
  }

  if ($loadingConfig) {
      $loadingTheme = $loadingConfig['theme'];
      $loadingTitle = $loadingConfig['title'];
      $loadingSubtitle = $loadingConfig['subtitle'];
      $loadingFooter = $loadingConfig['footer'];
      $pageType = $loadingConfig['pageType'];
      $terminalMessages = $loadingConfig['messages'];
      $loadingDuration = $loadingConfig['duration'];
  }
  ?>
  <?php if ($page !== 'login'): ?>
    <?php include BASE_PATH . '/views/loading.php'; ?>
  <?php endif; ?>
  <div class="wrapper">
    <?php if ($page !== 'login'): ?>
    <header class="topnav">
      <a class="topnav-brand" href="/newqr">
        <span>◫</span> HWASEUNG
      </a>
      <button class="topnav-toggle" id="topnavToggle" aria-label="Toggle navigation">☰</button>
      <ul class="topnav-links" id="topnavLinks">
        <li><a href="/newqr" class="<?=($page=='home')?'active':''?>"><span class="icon">⊞</span> Dashboard</a></li>
        <li><a href="/newqr/data-qr" class="<?=($page=='dt')?'active':''?>"><span class="icon">☰</span> Data QR</a></li>
        <li><a href="/newqr/import" class="<?=($page=='im')?'active':''?>"><span class="icon">⤓</span> Import Data</a></li>
        <li><a href="/newqr/buat-qr" class="<?=($page=='qr')?'active':''?>"><span class="icon">◱</span> QR Code PC</a></li>
        <li><a href="http://10.10.42.239:8006/abc/" target="_blank" rel="noopener"><span class="icon">⌖</span> BUAT SCAN WIE DLL</a></li>
        <?php if (!empty($_SESSION['user_id'])): ?>
          <li><a href="/newqr/actions/logout.php" class="logout-link"><span class="icon">⏻</span> Logout</a></li>
        <?php else: ?>
          <li><a href="/newqr/login" class="<?=($page=='login')?'active':''?>"><span class="icon">🔒</span> Login</a></li>
        <?php endif; ?>
      </ul>
    </header>

    <main class="main-content">
<?php
if ($page == 'print' && isset($dataType) && $dataType == 'paxar') {
    require_once __DIR__ . '/app/Presenters/PaxarPresenter.php';
    PaxarPresenter::render($db, $version);
} elseif ($page == 'print' && isset($dataType) && $dataType == 'tl') {
    require_once __DIR__ . '/app/Presenters/TlPresenter.php';
    TlPresenter::render($db, $version);
} elseif ($page == 'print' && isset($dataType) && $dataType == 'additional_label') {
    require_once __DIR__ . '/app/Presenters/AdditionalLabelPrintPresenter.php';
    AdditionalLabelPrintPresenter::render($db, $version, $printVariant);
} elseif ($page == 'print' && isset($dataType) && $dataType != 'paxar') {
    require_once __DIR__ . '/app/Presenters/PrintPresenter.php';
    PrintPresenter::render($db, $version, 'regular');
} elseif ($page == 'print2' && isset($dataType) && $dataType != 'paxar') {
    require_once __DIR__ . '/app/Presenters/PrintPresenter.php';
    PrintPresenter::render($db, $version, 'xerox');
} else {
    if ($page == 'home') {
        require_once __DIR__ . '/app/Presenters/DashboardPresenter.php';
        DashboardPresenter::render($db);
    } elseif ($page == 'dt') {
        require_once __DIR__ . '/app/Presenters/DataQrPresenter.php';
        DataQrPresenter::render($db);
    } elseif ($page == 'im') {
        require_once __DIR__ . '/app/Presenters/ImportPresenter.php';
        ImportPresenter::render($db);
    } elseif ($page == 'qr') {
        require_once __DIR__ . '/app/Presenters/QrGeneratorPresenter.php';
        QrGeneratorPresenter::render($db);
    }
}
?>
    </main>
    <?php endif; ?>
    <?php if ($page !== 'login'): ?>
    <footer class="site-footer">
      <div class="footer-inner">
        <span>&copy; <?= date('Y') ?> HWASEUNG INDONESIA</span>
        <span class="footer-sep">|</span>
        <span class="footer-credit">Project created by dicky.lbl</span>
      </div>
    </footer>
    <?php endif; ?>
  </div>

  <script type="text/javascript">
    const pageType = '<?= htmlspecialchars($pageType ?? 'default', ENT_QUOTES, 'UTF-8') ?>';
    const terminalMessages = <?= json_encode($terminalMessages ?? []) ?>;
    const loadingDuration = <?= (int)($loadingDuration ?? 3500) ?>;
    
    const hackingMessages = {
      print: [
        { text: "> HWASEUNG: Establishing secure connection...", type: "command" },
        { text: "> HWASEUNG: Bypassing firewall...", type: "command" },
        { text: "> HWASEUNG: Access granted!", type: "success" },
        { text: "> HWASEUNG: Loading print module...", type: "command" },
        { text: "> HWASEUNG: Decrypting QR data...", type: "command" },
        { text: "> HWASEUNG: Rendering layout...", type: "command" },
        { text: "> HWASEUNG: System ready!", type: "success" }
      ],
      import: [
        { text: "> HWASEUNG: Scanning file system...", type: "command" },
        { text: "> HWASEUNG: Detecting Excel format...", type: "command" },
        { text: "> HWASEUNG: Parsing data structure...", type: "command" },
        { text: "> HWASEUNG: Validating integrity...", type: "warning" },
        { text: "> HWASEUNG: Importing records...", type: "command" },
        { text: "> HWASEUNG: Generating QR codes...", type: "command" },
        { text: "> HWASEUNG: Database synchronized!", type: "success" }
      ],
      home: [
        { text: "> HWASEUNG: Loading dashboard modules...", type: "command" },
        { text: "> HWASEUNG: Fetching statistics...", type: "command" },
        { text: "> HWASEUNG: Syncing with database...", type: "command" },
        { text: "> HWASEUNG: Rendering interface...", type: "command" },
        { text: "> HWASEUNG: System ready!", type: "success" }
      ],
      upload: [
        { text: "> HWASEUNG: Validating input data...", type: "command" },
        { text: "> HWASEUNG: Generating QR matrix...", type: "command" },
        { text: "> HWASEUNG: Encoding payload...", type: "command" },
        { text: "> HWASEUNG: Rendering QR codes...", type: "command" },
        { text: "> HWASEUNG: Process complete!", type: "success" }
      ],
      default: [
        { text: "> HWASEUNG: Initializing system...", type: "command" },
        { text: "> HWASEUNG: Loading modules...", type: "command" },
        { text: "> HWASEUNG: Ready!", type: "success" }
      ]
    };

    function initMatrix() {
      const canvas = document.getElementById('matrixCanvas');
      if (!canvas) return;
      
      const overlay = document.getElementById('loadingOverlay');
      if (!overlay || !overlay.classList.contains('theme-hack')) {
        canvas.style.display = 'none';
        return;
      }
      
      const ctx = canvas.getContext('2d');
      
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
      
      const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#$%^&*(){}[]<>?';
      const fontSize = 14;
      const columns = Math.floor(canvas.width / fontSize);
      const drops = Array(columns).fill(1);
      
      function draw() {
        ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        ctx.fillStyle = '#0f0';
        ctx.font = fontSize + 'px monospace';
        
        for (let i = 0; i < drops.length; i++) {
          const text = chars[Math.floor(Math.random() * chars.length)];
          ctx.fillText(text, i * fontSize, drops[i] * fontSize);
          
          if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
            drops[i] = 0;
          }
          drops[i]++;
        }
      }
      
      const matrixInterval = setInterval(draw, 50);
      
      return matrixInterval;
    }

    function addTerminalLine(text, type, theme) {
      const terminal = document.getElementById('loadingTerminal');
      if (!terminal) return;
      
      const line = document.createElement('div');
      line.className = 'loading-terminal-line';
      
      const timestamp = new Date().toLocaleTimeString('en-US', { hour12: false });
      let typeClass = 'command';
      if (type === 'success') typeClass = 'success';
      else if (type === 'warning') typeClass = 'warning';
      else if (type === 'error') typeClass = 'error';
      
      line.innerHTML = '<span class="prefix">[' + timestamp + ']</span><span class="' + typeClass + '">' + text + '</span>';
      terminal.appendChild(line);
      terminal.scrollTop = terminal.scrollHeight;
    }

    function animateLoading(context) {
      const bar = document.getElementById('loadingBar');
      const percentage = document.getElementById('loadingPercentage');
      const status = document.getElementById('loadingStatus');
      const overlay = document.getElementById('loadingOverlay');
      
      let progress = 0;
      let msgIndex = 0;
      let completed = false;
      let interval = null;
      
      function finish() {
        if (completed) return;
        completed = true;
        if (interval) clearInterval(interval);
        if (bar) bar.style.width = '100%';
        if (percentage) percentage.textContent = '100%';
        if (status) status.textContent = 'STATUS: ACCESS GRANTED';
        
        const completeText = context === 'import' ? '> HWASEUNG: Import complete. Data ready.' :
                             context === 'upload' ? '> HWASEUNG: QR generation complete.' :
                             context === 'home' ? '> HWASEUNG: Dashboard loaded successfully.' :
                             '> HWASEUNG: Operation complete.';
        addTerminalLine(completeText, "success");
        
        if (overlay) {
          setTimeout(function() {
            overlay.classList.remove('active');
          }, 600);
        }
      }
      
      if (!bar || !overlay || !overlay.classList.contains('active')) {
        return { finish: finish };
      }
      
      const messages = terminalMessages.length > 0 ? terminalMessages : (hackingMessages[context] || hackingMessages.default);
      
      const startText = context === 'import' ? '> HWASEUNG: Starting import process...' : 
                        context === 'upload' ? '> HWASEUNG: Starting QR generation...' :
                        context === 'home' ? '> HWASEUNG: Booting dashboard system...' :
                        '> HWASEUNG: Initializing system...';
      addTerminalLine(startText, "command");
      
      interval = setInterval(function() {
        if (completed) return;
        progress += Math.random() * 12 + 3;
        if (progress > 95) progress = 95;
        
        bar.style.width = progress + '%';
        percentage.textContent = Math.floor(progress) + '%';
        
        while (msgIndex < messages.length && progress >= ((msgIndex + 1) / messages.length) * 100) {
          addTerminalLine(messages[msgIndex].text, messages[msgIndex].type);
          if (messages[msgIndex].type === 'success') {
            status.textContent = 'STATUS: COMPLETE';
          } else if (messages[msgIndex].type === 'warning') {
            status.textContent = 'STATUS: WARNING';
          } else {
            status.textContent = 'STATUS: PROCESSING';
          }
          msgIndex++;
        }
      }, 250);

      window.addEventListener('load', function() {
        finish();
      });

      setTimeout(function() {
        finish();
      }, loadingDuration);
      
      return { finish: finish };
    }

    $(function() {
      const overlay = $('#loadingOverlay');
      overlay.removeClass('active');
      
      const matrixInterval = initMatrix();
      
      let currentLoader = null;
      
      if (pageType === 'home' || pageType === 'import' || pageType === 'upload') {
        overlay.addClass('active');
        currentLoader = animateLoading(pageType);
      }

      if ($('#dataqr').length) {
        $('#dataqr').DataTable();
      }
      
      window.loadingLoader = currentLoader;
    });

    document.addEventListener('submit', function(e) {
      const form = e.target;
      const btn = form.querySelector('button[type="submit"]');
      if (btn) {
        btn.classList.add('btn-loading');
        btn.disabled = true;
      }
      
      const overlay = document.getElementById('loadingOverlay');
      const formAction = form.action || '';
      
      if (formAction.indexOf('/actions/import.php') !== -1) {
        e.preventDefault();
        
        Notification.clear();
        
        const formData = new FormData(form);
        if (overlay) {
          overlay.classList.add('active');
          const terminal = document.getElementById('loadingTerminal');
          if (terminal) terminal.innerHTML = '';
        }
        const loader = animateLoading('import');
        
        fetch(formAction, {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (loader && loader.finish) {
            loader.finish();
          } else if (overlay) {
            overlay.classList.remove('active');
          }
          
          if (btn) {
            btn.classList.remove('btn-loading');
            btn.disabled = false;
          }
          
          if (data.success) {
            Notification.show('Berhasil', data.message, 'success');
            form.reset();
          } else {
            const errorHtml = Notification.buildErrorTable(data.errors);
            Notification.show('Format Tidak Valid', errorHtml, 'error', true);
          }
        })
        .catch(err => {
          if (loader && loader.finish) {
            loader.finish();
          } else if (overlay) {
            overlay.classList.remove('active');
          }
          
          if (btn) {
            btn.classList.remove('btn-loading');
            btn.disabled = false;
          }
          
          let errorMsg = 'Terjadi kesalahan sistem. Silakan coba lagi.';
          if (err && err.message) {
            errorMsg = 'Error: ' + err.message;
          }
          Notification.show('Error', errorMsg, 'error');
        });
      } else if (formAction.indexOf('/actions/login.php') !== -1) {
        e.preventDefault();
        
        Notification.clear();
        
        const formData = new FormData(form);
        const loader = animateLoading('import');
        
        fetch(formAction, {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (loader && loader.finish) {
            loader.finish();
          }
          
          if (btn) {
            btn.classList.remove('btn-loading');
            btn.disabled = false;
          }
          
          if (data.success) {
            Notification.show('Berhasil', 'Login berhasil', 'success');
            setTimeout(() => {
              window.location.href = data.redirect || '/newqr';
            }, 500);
          } else {
            const errorHtml = Notification.buildErrorTable(data.errors);
            Notification.show('Login Gagal', errorHtml, 'error', true);
          }
        })
        .catch(err => {
          if (loader && loader.finish) {
            loader.finish();
          }
          
          if (btn) {
            btn.classList.remove('btn-loading');
            btn.disabled = false;
          }
          
          Notification.show('Error', 'Terjadi kesalahan sistem. Silakan coba lagi.', 'error');
        });
      } else if (formAction.indexOf('/buat-qr') !== -1 || form.id === 'qrForm') {
        if (overlay) {
          overlay.classList.add('active');
          const terminal = document.getElementById('loadingTerminal');
          if (terminal) terminal.innerHTML = '';
          
          const qrTheme = overlay.classList.contains('theme-hack') ? 'theme-hack' : 'theme-system';
          animateLoading('upload');
        }
      }
    });

    document.addEventListener('click', function(e) {
      if (e.target.closest('.btn-delete')) {
        const btn = e.target.closest('.btn-delete');
        btn.classList.add('btn-loading');
        btn.style.pointerEvents = 'none';
      }
    });
    
    const Notification = {
      container: document.getElementById('notificationArea'),
      
      show(title, message, type, html) {
        if (!this.container) return;
        
        const notification = document.createElement('div');
        notification.className = 'notification notification-' + type;
        
        const icons = {
          success: '&#10004;',
          error: '&#10008;',
          warning: '&#9888;'
        };
        
        const messageContent = html ? message : this.escapeHtml(message);
        
        notification.innerHTML = 
          '<div class="notification-icon">' + (icons[type] || '&#9432;') + '</div>' +
          '<div class="notification-content">' +
            '<div class="notification-title">' + this.escapeHtml(title) + '</div>' +
            '<div class="notification-message">' + messageContent + '</div>' +
          '</div>' +
          '<button class="notification-close">&times;</button>';
        
        this.container.appendChild(notification);
        
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => this.dismiss(notification));
        
        this.container.scrollTop = this.container.scrollHeight;
      },
      
      dismiss(notification) {
        if (notification && !notification.classList.contains('toast-exiting')) {
          notification.classList.add('toast-exiting');
          setTimeout(() => {
            if (notification.parentNode) notification.parentNode.removeChild(notification);
          }, 300);
        }
      },
      
      clear() {
        if (!this.container) return;
        const notifications = this.container.querySelectorAll('.notification');
        notifications.forEach(n => this.dismiss(n));
      },
      
      escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
      },
      
      buildErrorTable(errors) {
        if (!errors || !errors.length) return 'Terjadi kesalahan validasi data.';
        
        let tableHtml = '<table class="notification-table"><thead><tr>' +
          '<th>Baris</th>' +
          '<th>Kolom</th>' +
          '<th>Bidang</th>' +
          '<th>Nilai Saat Ini</th>' +
          '<th>Tipe Kesalahan</th>' +
          '<th>Saran Perbaikan</th>' +
          '</tr></thead><tbody>';
        
        errors.forEach(function(err) {
          const row = err.row || '-';
          const col = err.col || '-';
          const field = err.field || '-';
          const value = err.value || '-';
          const type = err.type || 'unknown';
          const suggestion = err.suggestion || 'Periksa kembali format data sesuai template.';
          
          let typeLabel = type;
          switch(type) {
            case 'header_mismatch': typeLabel = 'Header Salah'; break;
            case 'required_empty': typeLabel = 'Kosong/Wajib'; break;
            case 'invalid_numeric': typeLabel = 'Bukan Angka'; break;
            case 'invalid_date': typeLabel = 'Format Tanggal'; break;
            case 'column_count': typeLabel = 'Jumlah Kolom'; break;
            case 'max_length_exceeded': typeLabel = 'Terlalu Panjang'; break;
          }
          
          tableHtml += '<tr>' +
            '<td>' + this.escapeHtml(String(row)) + '</td>' +
            '<td>' + this.escapeHtml(String(col)) + '</td>' +
            '<td class="expected-value">' + this.escapeHtml(field) + '</td>' +
            '<td class="current-value">' + this.escapeHtml(String(value)) + '</td>' +
            '<td>' + this.escapeHtml(typeLabel) + '</td>' +
            '<td>' + this.escapeHtml(suggestion) + '</td>' +
            '</tr>';
        }.bind(this));
        
        tableHtml += '</tbody></table>';
        tableHtml += '<div class="notification-instruction"><strong>Petunjuk:</strong> Perbaiki sesuai tabel di atas. Pastikan Format Cells dan header sama persis dengan template yang disediakan.</div>';
        
        return tableHtml;
      }
    };
  </script>
  <script>
    (function() {
      var toggle = document.getElementById('topnavToggle');
      var links = document.getElementById('topnavLinks');
      if (!toggle || !links) return;
      toggle.addEventListener('click', function() {
        links.classList.toggle('open');
      });
    })();
  </script>
</body>

</html>
<?php endif; ?>
