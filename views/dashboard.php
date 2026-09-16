<div class="hero-section">
  <h1>Dashboard</h1>
  <p>Kelola dan pantau sistem cetak QR dengan mudah dan cepat</p>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon stat-icon-box" style="background:#eef2ff;color:#4f46e5;">📦</div>
    <div class="stat-info">
      <h3><?=$uploadCount?></h3>
      <p>Upload Hari Ini</p>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon stat-icon-box" style="background:#ecfdf5;color:#10b981;">🟢</div>
    <div class="stat-info">
      <h3>Online</h3>
      <p>Sistem Aktif</p>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon stat-icon-box" style="background:#fff7ed;color:#f59e0b;">👤</div>
    <div class="stat-info">
      <h3><?=htmlspecialchars($_SESSION['user_id'] ?? 'Guest', ENT_QUOTES, 'UTF-8')?></h3>
      <p>Pengguna Aktif</p>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-sm-6">
    <div class="card">
      <div class="card-header">Navigasi Cepat</div>
      <div class="nav-actions">
        <a href="/newqr/data-qr" class="btn btn-primary">📋 Lihat Data QR</a>
        <a href="/newqr/import" class="btn btn-success">⬆️ Import Baru</a>
        <a href="/newqr/buat-qr" class="btn btn-info">◱ QR Code PC</a>
      </div>
    </div>
  </div>
  <div class="col-sm-6">
    <div class="card">
      <div class="card-header">Informasi Sistem</div>
      <p class="text-muted-custom">
        Sistem siap digunakan untuk import dan cetak QR code. Pastikan file Excel sesuai template yang tersedia.
      </p>
    </div>
  </div>
</div>
