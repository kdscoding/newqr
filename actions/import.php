<?php
require_once __DIR__ . '/../app/Config.php';
require_once __DIR__ . '/../app/Services/AuthService.php';
require_once __DIR__ . '/../app/Services/ExcelImportService.php';
require_once __DIR__ . '/../excel_reader.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

AuthService::check();

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'errors' => ['Invalid CSRF token']]);
    exit;
}

$koneksi = Config::db();

header('Content-Type: application/json');

try {
    if (isset($_POST['data'])) {
        $result = ExcelImportService::process($koneksi, $_POST['data'], $_FILES['file_excel']);
        
        if (!$result['success']) {
            echo json_encode([
                'success' => false,
                'errors' => $result['errors']
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Upload version anda: ' . ($result['version'] ?? '') . ' | ' . ($result['imported'] ?? 0) . ' baris diimport' . (($result['skipped'] ?? 0) > 0 ? ', ' . $result['skipped'] . ' baris di-skip (ID kosong)' : '')
        ]);
        exit;
    }

    echo json_encode([
        'success' => false,
        'errors' => ['Invalid request']
    ]);
    exit;
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'errors' => [
            [
                'row' => '-',
                'col' => '-',
                'field' => 'SYSTEM',
                'value' => $e->getMessage(),
                'type' => 'system_error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
                'suggestion' => 'Silakan hubungi administrator atau coba lagi dengan file yang berbeda.'
            ]
        ]
    ]);
    exit;
}
