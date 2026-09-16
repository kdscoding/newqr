<?php
$loadingTheme = $loadingTheme ?? 'theme-hack';
$loadingTitle = $loadingTitle ?? 'HWASEUNG';
$loadingSubtitle = $loadingSubtitle ?? 'INITIALIZING SYSTEM';
$loadingAscii = $loadingAscii ?? '';
$loadingFooter = $loadingFooter ?? 'HWASEUNG INDONESIA';
$pageType = $pageType ?? 'default';
$terminalMessages = $terminalMessages ?? [];
$loadingDuration = $loadingDuration ?? 3500;
?>
<div class="loading-overlay <?= htmlspecialchars($loadingTheme) ?>" id="loadingOverlay">
  <canvas id="matrixCanvas"></canvas>
  <div class="loading-content">
    <?php if ($loadingAscii): ?>
    <div class="loading-ascii"><?= $loadingAscii ?></div>
    <?php endif; ?>
    
    <div class="loading-title"><?= htmlspecialchars($loadingTitle) ?></div>
    <div class="loading-subtitle"><?= htmlspecialchars($loadingSubtitle) ?></div>
    
    <div class="loading-terminal" id="loadingTerminal"></div>
    
    <div class="loading-bar-container">
      <div class="loading-bar" id="loadingBar"></div>
    </div>
    
    <div class="loading-status">
      <span id="loadingStatus">STATUS: ACCESSING...</span>
      <span class="loading-percentage" id="loadingPercentage">0%</span>
    </div>
    
    <div class="loading-footer"><?= htmlspecialchars($loadingFooter) ?></div>
  </div>
</div>
