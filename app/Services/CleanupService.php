<?php
class CleanupService
{
    private const MAX_AGE = 86400; // 24 hours in seconds

    public static function run($dryRun = false)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $lastCleanup = $_SESSION['last_cleanup'] ?? 0;
        $now = time();

        if ($now - $lastCleanup < 3600) {
            return ['skipped' => true, 'reason' => 'cleanup ran within last hour'];
        }

        $_SESSION['last_cleanup'] = $now;

        $results = [
            'upload' => self::cleanDirectory(BASE_PATH . '/FILE/UPLOAD/', ['xls'], $dryRun),
            'qr' => self::cleanDirectory(BASE_PATH . '/QR/', ['png', 'jpg', 'jpeg'], $dryRun),
            'qr_generated' => self::cleanDirectory(BASE_PATH . '/QR/generated/', ['png', 'jpg', 'jpeg'], $dryRun)
        ];

        return $results;
    }

    private static function cleanDirectory($dir, $extensions, $dryRun)
    {
        $deleted = 0;
        $errors = [];

        if (!is_dir($dir)) {
            return ['deleted' => 0, 'errors' => ["Directory not found: $dir"]];
        }

        $files = scandir($dir);
        if ($files === false) {
            return ['deleted' => 0, 'errors' => ["Failed to scan directory: $dir"]];
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $dir . $file;

            if (!is_file($filePath)) {
                continue;
            }

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, $extensions, true)) {
                continue;
            }

            $fileAge = time() - filemtime($filePath);
            if ($fileAge > self::MAX_AGE) {
                if ($dryRun) {
                    $deleted++;
                } else {
                    if (@unlink($filePath)) {
                        $deleted++;
                    } else {
                        $errors[] = "Failed to delete: $filePath";
                    }
                }
            }
        }

        return [
            'deleted' => $deleted,
            'errors' => $errors
        ];
    }
}
