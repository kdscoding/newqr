<?php
class QrGeneratorPresenter {
    public static function render($db) {
        $rows = [];
        $qrResults = [];
        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
        
        if ($isPost && isset($_POST['rows'])) {
            $rawRows = $_POST['rows'];
            $count = 0;
            foreach (['nama', 'printer', 'pc', 'ip_pc', 'ip_printer'] as $key) {
                if (isset($rawRows[$key]) && is_array($rawRows[$key]) && count($rawRows[$key]) > $count) {
                    $count = count($rawRows[$key]);
                }
            }
            $count = min(51, max(1, $count));
            
            for ($i = 0; $i < $count; $i++) {
                $nama = isset($rawRows['nama'][$i]) ? trim($rawRows['nama'][$i]) : '';
                $printer = isset($rawRows['printer'][$i]) ? trim($rawRows['printer'][$i]) : '';
                $pc = isset($rawRows['pc'][$i]) ? trim($rawRows['pc'][$i]) : '';
                $ipPc = isset($rawRows['ip_pc'][$i]) ? trim($rawRows['ip_pc'][$i]) : '';
                $ipPrinter = isset($rawRows['ip_printer'][$i]) ? trim($rawRows['ip_printer'][$i]) : '';
                
                if ($nama === '' && $printer === '' && $pc === '' && $ipPc === '' && $ipPrinter === '') {
                    continue;
                }
                
                $rows[] = [
                    'nama' => $nama,
                    'printer' => $printer,
                    'pc' => $pc,
                    'ip_pc' => $ipPc,
                    'ip_printer' => $ipPrinter,
                ];
            }
            
            $qrDir = BASE_PATH . '/QR/generated/';
            if (!is_dir($qrDir)) {
                mkdir($qrDir, 0755, true);
            }
            
            foreach ($rows as $idx => $row) {
                $label = $row['nama'] ?: 'qr_' . ($idx + 1);
                $qrText = $label;
                
                $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $label);
                $fileName = 'qr_' . md5($qrText . microtime()) . '_' . substr($safeName, 0, 20) . '.png';
                $filePath = $qrDir . $fileName;
                $qrImage = BASE_URL . '/QR/generated/' . $fileName;
                
                if (!file_exists($filePath)) {
                    require_once BASE_PATH . '/phpqrcode/qrlib.php';
                    QRcode::png($qrText, $filePath, 'L', 6, 2);
                }
                
                $qrResults[] = [
                    'nama' => $row['nama'],
                    'printer' => $row['printer'],
                    'pc' => $row['pc'],
                    'ip_pc' => $row['ip_pc'],
                    'ip_printer' => $row['ip_printer'],
                    'qr_image' => $qrImage,
                ];
            }
        } else {
            for ($i = 0; $i < 3; $i++) {
                $rows[] = [
                    'nama' => '',
                    'printer' => '',
                    'pc' => '',
                    'ip_pc' => '',
                    'ip_printer' => '',
                ];
            }
        }
        
        include BASE_PATH . '/views/qr-generator.php';
    }
}
