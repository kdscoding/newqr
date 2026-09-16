<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loginError = $_SESSION['login_error'] ?? '';
$loginMessage = '';

if ($loginError !== '') {
    unset($_SESSION['login_error']);
}

if (isset($_GET['timeout'])) {
    $loginMessage = 'Session expired due to inactivity (30 minutes). Please login again.';
} elseif (isset($_GET['expired'])) {
    $loginMessage = 'Session expired (8 hours). Please login again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HWASEUNG INDONESIA - Secure Access</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      background: #000;
      color: #0f0;
      font-family: 'Courier New', 'Consolas', monospace;
      min-height: 100vh;
      overflow: hidden;
    }
    #loginMatrix {
      position: fixed;
      inset: 0;
      z-index: 0;
      opacity: 0.35;
    }
    .login-hack-box {
      position: relative;
      z-index: 2;
      width: 420px;
      max-width: 92vw;
      background: #1a1a1a;
      border: 1px solid #3d3d3d;
      border-radius: 2px;
      padding: 24px;
      box-shadow: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      animation: fadeInUp 0.6s ease-out;
    }
    .login-hack-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 6px;
    }
    .login-hack-icon {
      font-size: 26px;
      color: #d4d4d4;
      text-shadow: none;
    }
    .login-hack-title {
      font-size: 16px;
      font-weight: 700;
      letter-spacing: 1px;
      color: #d4d4d4;
      text-shadow: none;
    }
    .login-hack-subtitle {
      font-size: 11px;
      color: #888;
      margin-bottom: 16px;
      letter-spacing: 1.5px;
      text-shadow: none;
    }
    .login-hack-terminal {
      background: #1a1a1a;
      border: 1px solid #3d3d3d;
      border-radius: 2px;
      padding: 10px 12px;
      min-height: 32px;
      margin-bottom: 16px;
      font-size: 12px;
      color: #d4d4d4;
      text-shadow: none;
      box-shadow: none;
    }
    .login-hack-wrapper .form-group {
      margin-bottom: 14px;
    }
    .login-hack-wrapper label {
      display: block;
      font-size: 11px;
      font-weight: 700;
      color: #b0b0b0;
      margin-bottom: 6px;
      letter-spacing: 1px;
      text-shadow: none;
    }
    .login-hack-wrapper .form-control {
      background: #2d2d2d;
      border: 1px solid #3d3d3d;
      border-radius: 2px;
      padding: 8px 10px;
      color: #d4d4d4;
      font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
      font-size: 13px;
      box-shadow: none;
      outline: none;
      width: 100%;
    }
    .login-hack-wrapper .form-control::placeholder {
      color: #777;
    }
    .login-hack-wrapper .form-control:focus {
      border-color: #555;
      box-shadow: none;
    }
    .login-hack-btn {
      background: #3d3d3d;
      border: 1px solid #555;
      color: #d4d4d4;
      font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-shadow: none;
      box-shadow: none;
      transition: all 0.2s;
      width: 100%;
      padding: 10px 12px;
      border-radius: 2px;
      cursor: pointer;
    }
    .login-hack-btn:hover {
      background: #4d4d4d;
      box-shadow: none;
      color: #fff;
    }
    .login-hack-footer {
      margin-top: 14px;
      text-align: center;
      font-size: 10px;
      color: #777;
      letter-spacing: 1.5px;
      text-shadow: none;
      opacity: 0.8;
    }
    .login-error {
      background: #2d1a1a;
      border: 1px solid #8b0000;
      color: #ff6b6b;
      text-shadow: none;
      padding: 10px;
      border-radius: 2px;
      margin-bottom: 14px;
      font-size: 12px;
    }
    .login-message {
      background: #1a1a2e;
      border: 1px solid #444;
      color: #d4d4d4;
      text-shadow: none;
      padding: 10px;
      border-radius: 2px;
      margin-bottom: 14px;
      font-size: 12px;
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translate(-50%, -45%); }
      to { opacity: 1; transform: translate(-50%, -50%); }
    }
  </style>
</head>
<body>
  <canvas id="loginMatrix"></canvas>
  <div class="login-hack-box">
    <div class="login-hack-header">
      <span class="login-hack-icon">◫</span>
      <span class="login-hack-title">HWASEUNG INDONESIA</span>
    </div>
    <div class="login-hack-subtitle">SECURE ACCESS TERMINAL v2.0</div>
    <div class="login-hack-terminal" id="loginTerminal"></div>

    <?php if ($loginMessage): ?>
      <div class="login-message"><?= htmlspecialchars($loginMessage, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($loginError): ?>
      <div class="login-error"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="/newqr/actions/login.php" id="loginForm" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <div class="form-group">
        <label for="username">USERNAME</label>
        <input type="text" class="form-control" id="username" name="username" required autofocus autocomplete="off">
      </div>
      <div class="form-group">
        <label for="password">PASSWORD</label>
        <input type="password" class="form-control" id="password" name="password" required autocomplete="off">
      </div>
      <button type="submit" class="login-hack-btn">
        <span class="btn-text">AUTHENTICATE</span>
      </button>
    </form>
    <div class="login-hack-footer">
      AUTHORIZED PERSONNEL ONLY
    </div>
  </div>

  <script>
    (function() {
      const canvas = document.getElementById('loginMatrix');
      if (!canvas) return;
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

      setInterval(draw, 50);

      const terminal = document.getElementById('loginTerminal');
      const messages = [
        '> HWASEUNG: Initializing secure terminal...',
        '> HWASEUNG: Loading authentication module...',
        '> HWASEUNG: Bypassing firewall...',
        '> HWASEUNG: Encryption keys verified.',
        '> HWASEUNG: System ready. Please login.'
      ];

      let msgIndex = 0;
      function addTerminalLine(text) {
        if (!terminal) return;
        const line = document.createElement('div');
        line.style.marginBottom = '4px';

        const timestamp = new Date().toLocaleTimeString('en-US', { hour12: false });
        line.innerHTML = '<span style="opacity:0.7">[' + timestamp + ']</span> <span style="color:#d4d4d4">' + text + '</span>';
        terminal.appendChild(line);
        terminal.scrollTop = terminal.scrollHeight;
      }

      let interval = setInterval(function() {
        if (msgIndex >= messages.length) {
          clearInterval(interval);
          return;
        }
        addTerminalLine(messages[msgIndex]);
        msgIndex++;
      }, 300);

      window.addEventListener('resize', function() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
      });
    })();
  </script>
</body>
</html>