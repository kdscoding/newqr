<?php
class BsomPresenter
{
    private const BSOM_SOURCE = 'http://10.10.10.98/bsom/';

    public static function render($db)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $rawPath = isset($_GET['path']) ? (string)$_GET['path'] : '';
        $path = self::normalizePath($rawPath);
        $path = rtrim($path, '/');

        $sortCol = isset($_GET['sort']) ? (string)$_GET['sort'] : 'name';
        $sortDir = isset($_GET['dir']) ? (string)$_GET['dir'] : 'asc';
        $sortCol = in_array($sortCol, ['name', 'modified', 'size'], true) ? $sortCol : 'name';
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'asc';

        $entries = [];
        $error = null;
        $parentPath = ($path !== '') ? $path : null;
        $pathEncoded = self::encodeRel($path);

        $fetchUrl = self::BSOM_SOURCE . $pathEncoded;
        if (substr($fetchUrl, -1) !== '/') {
            $fetchUrl .= '/';
        }

        $html = self::fetch($fetchUrl);
        if ($html === false) {
            $error = 'Tidak dapat mengambil data dari server bsom. Pastikan koneksi jaringan ke 10.10.10.98 tersedia.';
        } else {
            $entries = self::parse($html, $path);
            usort($entries, function ($a, $b) use ($sortCol, $sortDir) {
                $ka = self::sortKey($a, $sortCol);
                $kb = self::sortKey($b, $sortCol);
                $cmp = ($ka <=> $kb);
                return $sortDir === 'desc' ? -$cmp : $cmp;
            });
        }

        $currentDisplay = self::BSOM_SOURCE . ltrim($pathEncoded, '/');

        include BASE_PATH . '/views/bsom.php';
    }

    private static function fetch($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Accept: text/html'],
        ]);
        $data = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        if ($errno || $data === false) {
            return false;
        }
        return $data;
    }

    private static function normalizePath($path): string
    {
        $leading = str_starts_with($path, '/');
        $segs = explode('/', $path);
        $out = [];
        foreach ($segs as $seg) {
            if ($seg === '' || $seg === '.') {
                continue;
            }
            if ($seg === '..') {
                if (!empty($out)) {
                    array_pop($out);
                }
                continue;
            }
            $out[] = $seg;
        }
        $res = implode('/', $out);
        if ($leading) {
            return $res === '' ? '/' : '/' . $res;
        }
        return $res;
    }

    private static function encodeRel($within): string
    {
        $within = ltrim($within, '/');
        if ($within === '') {
            return '';
        }
        $segs = explode('/', $within);
        $segs = array_map('rawurlencode', $segs);
        return implode('/', $segs);
    }

    private static function cleanText($s): string
    {
        return trim(html_entity_decode((string)$s, ENT_QUOTES, 'UTF-8'));
    }

    private static function parse($html, $path): array
    {
        $pattern = '#<td[^>]*>\s*<img[^>]*alt="([^"]*)"[^>]*>\s*</td>\s*<td[^>]*>\s*<a\s+href="([^"]*)"[^>]*>(.*?)</a>\s*</td>\s*<td[^>]*>(.*?)</td>\s*<td[^>]*>(.*?)</td>\s*<td[^>]*>(.*?)</td>#is';
        if (!preg_match_all($pattern, $html, $m, PREG_SET_ORDER)) {
            return [];
        }

        $rows = [];
        foreach ($m as $row) {
            $alt = self::cleanText($row[1]);
            $href = $row[2];
            $modified = self::cleanText($row[4]);
            $size = self::cleanText($row[5]);

            if ($href === '' || ($href[0] ?? '') === '?') {
                continue;
            }

            $isDir = (substr($href, -1) === '/') || (stripos($alt, 'DIR') !== false);
            $isParent = stripos($alt, 'PARENTDIR') !== false;

            if (preg_match('#^https?://#i', $href) || str_starts_with($href, '/')) {
                $decoded = urldecode($href);
                $decoded = str_starts_with($href, '/') ? $decoded : urldecode(parse_url($href, PHP_URL_PATH));
                $decoded = self::normalizePath($decoded);
                if ($decoded === '/' || $decoded === '') {
                    continue;
                }
                $seg = explode('/', ltrim($decoded, '/'));
                if (empty($seg) || $seg[0] !== 'bsom') {
                    continue;
                }
                array_shift($seg);
                $within = implode('/', $seg);
            } else {
                $target = ($path === '') ? urldecode($href) : $path . '/' . urldecode($href);
                $within = self::normalizePath($target);
            }

            $within = ltrim($within, '/');
            $within = self::normalizePath($within);

            if ($within === '' && !$isDir) {
                continue;
            }

            if ($within === '') {
                $name = 'Parent Directory';
            } else {
                $segs = explode('/', $within);
                $name = urldecode(self::encodeRel(end($segs)));
            }
            if ($isDir && substr($name, -1) !== '/') {
                $name .= '/';
            }

            $encoded = self::encodeRel($within);

            if ($isDir) {
                $link = BASE_URL . '/bsom';
                if ($encoded !== '') {
                    $link .= '?path=' . $encoded;
                }
                $download = null;
            } else {
                $link = null;
                $download = BASE_URL . '/actions/bsom-download.php?file=' . $encoded;
            }

            $rows[] = [
                'name' => $name,
                'modified' => $modified,
                'size' => $size,
                'isDir' => $isDir,
                'isParent' => $isParent,
                'link' => $link,
                'download' => $download,
                'icon' => $isDir ? '📁' : '📄',
            ];
        }

        return $rows;
    }

    private static function sortKey(array $e, string $col)
    {
        if ($col === 'size') {
            return self::sizeToBytes($e['size']);
        }
        if ($col === 'modified') {
            return $e['modified'];
        }
        $name = $e['name'];
        return $e['isDir'] ? '0' . $name : '1' . $name;
    }

    private static function sizeToBytes(string $s): int
    {
        $s = trim($s);
        if ($s === '' || $s === '-' || strcasecmp($s, '-') === 0) {
            return -1;
        }
        if (preg_match('/^([0-9.]+)\s*([KMGkmg]?)(B?)$/', $s, $m)) {
            $n = (float)$m[1];
            $unit = strtoupper($m[2]);
            $mult = ['K' => 1024, 'M' => 1024 * 1024, 'G' => 1024 * 1024 * 1024];
            if ($unit === '') {
                return (int)$n;
            }
            return (int)($n * ($mult[$unit] ?? 1));
        }
        return 0;
    }
}
