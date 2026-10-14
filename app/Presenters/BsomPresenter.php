<?php
class BsomPresenter
{
    private const BSOM_SOURCE = 'http://10.10.10.98/bsom/';
    private static $fullIndexGeneratedAt = null;

    public static function render($db)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $rawPath = '';
        if (isset($_POST['path']) && is_scalar($_POST['path'])) {
            $rawPath = (string)$_POST['path'];
        } else {
            $rawPath = isset($_GET['path']) ? (string)$_GET['path'] : '';
        }
        $rawPath = self::decodeInputPath($rawPath);
        $path = self::normalizePath($rawPath);
        $path = rtrim($path, '/');

        $query = isset($_POST['q']) ? trim((string)$_POST['q']) : (isset($_GET['q']) ? trim((string)$_GET['q']) : '');
        $query = mb_substr($query, 0, 200);

        $rebuildMessage = null;
        $rebuildStatus = null;
        if (isset($_POST['rebuild_index']) || isset($_GET['rebuild_index'])) {
            $rebuildPaths = [];
            $rawRebuildPaths = $_POST['rebuild_paths'] ?? ($_GET['rebuild_paths'] ?? null);

            if (is_array($rawRebuildPaths)) {
                foreach ($rawRebuildPaths as $rawRebuildItem) {
                    if (!is_scalar($rawRebuildItem)) {
                        continue;
                    }
                    $normalizedPath = rtrim(self::normalizePath((string)$rawRebuildItem), '/');
                    if (strpos($normalizedPath, '..') === false && !in_array($normalizedPath, $rebuildPaths, true)) {
                        $rebuildPaths[] = $normalizedPath;
                    }
                }
            } elseif (isset($_POST['rebuild_path']) || isset($_GET['rebuild_path'])) {
                $normalizedPath = rtrim(self::normalizePath((string)($_POST['rebuild_path'] ?? ($_GET['rebuild_path'] ?? ''))), '/');
                if (strpos($normalizedPath, '..') === false) {
                    $rebuildPaths[] = $normalizedPath;
                }
            } else {
                $rebuildPaths[] = $path;
            }

            if ($rebuildPaths === []) {
                $rebuildMessage = 'Pilih minimal satu folder untuk rebuild index.';
                $rebuildStatus = 'warning';
            } else {
                if (count($rebuildPaths) > self::INDEX_REBUILD_MAX_BATCH) {
                    $rebuildPaths = array_slice($rebuildPaths, 0, self::INDEX_REBUILD_MAX_BATCH);
                    $rebuildMessage = 'Dibatasi ' . self::INDEX_REBUILD_MAX_BATCH . ' folder per rebuild.';
                }

                $successCount = 0;
                $failedCount = 0;
                $newEntryCount = 0;
                $requestCount = 0;
                $totalEntries = 0;
                $errors = [];

                foreach ($rebuildPaths as $rebuildPath) {
                    $rebuildResult = self::rebuildIndexScope($rebuildPath);
                    if (!empty($rebuildResult['ok'])) {
                        $successCount++;
                        $newEntryCount += (int)($rebuildResult['newEntries'] ?? 0);
                        $requestCount += (int)($rebuildResult['stats']['requests'] ?? 0);
                        $totalEntries = (int)($rebuildResult['totalEntries'] ?? 0);
                    } else {
                        $failedCount++;
                        $errors[] = ($rebuildPath !== '' ? $rebuildPath : 'root') . ': ' . (string)$rebuildResult['message'];
                    }
                }

                $rebuildMessage = 'Rebuild selesai: ' . $successCount . ' berhasil, ' . $failedCount .
                    ' gagal, ' . $newEntryCount . ' entry baru, total index ' . $totalEntries .
                    ', ' . $requestCount . ' request.';

                if ($successCount > 0 && $failedCount === 0) {
                    $rebuildStatus = 'success';
                } elseif ($successCount === 0) {
                    $rebuildStatus = 'error';
                } else {
                    $rebuildStatus = 'warning';
                }

                if ($errors !== []) {
                    $rebuildMessage .= ' Gagal: ' . implode('; ', $errors);
                }
            }
        }

        $sortCol = isset($_GET['sort']) ? (string)$_GET['sort'] : 'name';
        $sortDir = isset($_GET['dir']) ? (string)$_GET['dir'] : 'asc';
        $sortCol = in_array($sortCol, ['name', 'modified', 'size'], true) ? $sortCol : 'name';
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'asc';

        $indexRebuiltAt = self::lastIndexRebuiltAt();

        $entries = [];
        $error = null;
        $parentPath = ($path !== '') ? $path : null;
        $pathEncoded = self::encodeRel($path);
        $searchResults = null;
        $searchStats = null;
        $searchError = null;
        $searchTotal = 0;
        $groupedResults = null;
        // Auto-group when searching from root with full index available
        $autoGrouped = ($path === '' && $query !== '');

        if ($query !== '') {
            $res = self::searchBsom($query, $path);
            $searchResults = $res['results'];
            $searchStats = $res['stats'];
            $searchTotal = count($searchResults);
            if ($searchTotal === 0) {
                $searchError = 'Tidak ada hasil pada cakupan yang discanlei. Coba cari dari folder spesifik (mis. 018. SS27) untuk hasil pasti.';
            } elseif ($res['stats']['partial']) {
                $searchError = 'Hasil parsial (scanned dalam ' . $res['stats']['requests'] . ' request, batas waktu).';
            }
            
            if ($autoGrouped && $searchTotal > 0) {
                $groupedResults = self::groupBySeason($searchResults);
            }
        } else {
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
                    if ($sortCol === 'name') {
                        $cmp = strnatcasecmp($a['name'], $b['name']);
                    } else {
                        $ka = self::sortKey($a, $sortCol);
                        $kb = self::sortKey($b, $sortCol);
                        $cmp = ($ka <=> $kb);
                    }
                    return $sortDir === 'desc' ? -$cmp : $cmp;
                });
            }
        }

        $currentDisplay = self::BSOM_SOURCE . ltrim($pathEncoded, '/');

        include BASE_PATH . '/views/bsom.php';
    }

    public static function renderView($db)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $rawFile = isset($_GET['file']) ? (string)$_GET['file'] : '';
        $rawFile = self::decodeInputPath($rawFile);
        $file = self::normalizePath($rawFile);
        $file = ltrim($file, '/');

        $viewData = [
            'name' => '',
            'size' => '',
            'modified' => '',
            'preview' => null,
            'mime' => 'application/octet-stream',
            'viewType' => 'fallback',
            'downloadUrl' => null,
            'streamUrl' => null,
            'error' => null,
            'display' => self::BSOM_SOURCE . self::encodeRel($file),
            'file' => $file,
            'back' => null,
        ];

        // Back link target (only internal /bsom URLs allowed)
        $back = isset($_GET['back']) ? (string)$_GET['back'] : '';
        if ($back !== '' && $back[0] === '/' && strncmp($back, '//', 2) !== 0 && strpos($back, BASE_URL . '/bsom') === 0) {
            $viewData['back'] = $back;
        }

        if ($file === '' || strpos($file, '..') !== false) {
            $viewData['error'] = 'Berkas tidak valid.';
        } else {
            $url = self::BSOM_SOURCE . self::encodeRel($file);
            $data = self::fetchRaw($url, $viewData['mime'], $viewData['size'], $viewData['modified']);
            if ($data === false) {
                $viewData['error'] = 'Gagal mengambil berkas dari server bsom.';
            } else {
                $viewData['name'] = basename($file);
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $typeInfo = self::typeInfo($ext, $viewData['mime']);
                $viewData['mime'] = $typeInfo['mime'];
                $viewData['viewType'] = $typeInfo['viewType'];
                $viewData['downloadUrl'] = BASE_URL . '/actions/bsom-download.php?file=' . self::encodeRel($file);
                $viewData['streamUrl'] = BASE_URL . '/actions/bsom-stream.php?file=' . self::encodeRel($file);

                if (in_array($viewData['viewType'], ['image', 'pdf'], true)) {
                    $viewData['preview'] = null;
                } else {
                    $viewData['preview'] = $data;
                }
            }
        }

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

    private static function fetchRaw(string $url, string &$mime, string &$size, string &$modified)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
        ]);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'application/octet-stream';
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errno || $code !== 200 || $response === false) {
            return false;
        }
        $headers = substr($response, 0, $headerSize);
        $data = substr($response, $headerSize);
        $size = self::humanSize(strlen($data));
        $modified = '';
        if (preg_match('/Last-Modified:\s*(.+)\r?\n/i', $headers, $m)) {
            $ts = strtotime(trim($m[1]));
            if ($ts !== false) {
                $modified = date('Y-m-d H:i:s', $ts);
            } else {
                $modified = trim($m[1]);
            }
        }
        return $data;
    }

    private static function typeInfo(string $ext, string $curlMime): array
    {
        $map = [
            'txt' => ['mime' => 'text/plain', 'viewType' => 'text'],
            'log' => ['mime' => 'text/plain', 'viewType' => 'text'],
             'csv' => ['mime' => 'text/csv', 'viewType' => 'csv'],
             'json' => ['mime' => 'application/json', 'viewType' => 'text'],
             'htm' => ['mime' => 'text/html', 'viewType' => 'text'],
             'html' => ['mime' => 'text/html', 'viewType' => 'text'],
             'css' => ['mime' => 'text/css', 'viewType' => 'text'],
             'js' => ['mime' => 'application/javascript', 'viewType' => 'text'],
             'xml' => ['mime' => 'application/xml', 'viewType' => 'text'],
             'ini' => ['mime' => 'text/plain', 'viewType' => 'text'],
             'png' => ['mime' => 'image/png', 'viewType' => 'image'],
             'jpg' => ['mime' => 'image/jpeg', 'viewType' => 'image'],
             'jpeg' => ['mime' => 'image/jpeg', 'viewType' => 'image'],
             'gif' => ['mime' => 'image/gif', 'viewType' => 'image'],
             'svg' => ['mime' => 'image/svg+xml', 'viewType' => 'image'],
             'bmp' => ['mime' => 'image/bmp', 'viewType' => 'image'],
             'webp' => ['mime' => 'image/webp', 'viewType' => 'image'],
             'pdf' => ['mime' => 'application/pdf', 'viewType' => 'pdf'],
             'xlsx' => ['mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'viewType' => 'fallback'],
             'xls' => ['mime' => 'application/vnd.ms-excel', 'viewType' => 'fallback'],
             'docx' => ['mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'viewType' => 'fallback'],
             'pptx' => ['mime' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'viewType' => 'fallback'],
             'zip' => ['mime' => 'application/zip', 'viewType' => 'fallback'],
             'rar' => ['mime' => 'application/vnd.rar', 'viewType' => 'fallback'],
             '7z' => ['mime' => 'application/x-7z-compressed', 'viewType' => 'fallback'],
             'db' => ['mime' => 'application/octet-stream', 'viewType' => 'fallback'],
             'lnk' => ['mime' => 'application/octet-stream', 'viewType' => 'fallback'],
        ];
        if (isset($map[$ext])) {
            return $map[$ext];
        }
        if (str_starts_with($curlMime, 'text/')) {
            return ['mime' => $curlMime, 'viewType' => 'text'];
        }
        if (str_starts_with($curlMime, 'image/')) {
            return ['mime' => $curlMime, 'viewType' => 'image'];
        }
        return ['mime' => ($curlMime ?: 'application/octet-stream'), 'viewType' => 'fallback'];
    }

    private static function humanSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'K', 'M', 'G', 'T'];
        $pow = (int)floor(log($bytes, 1024));
        $pow = min($pow, count($units) - 1);
        $val = $bytes / (1024 ** $pow);
        return ($pow === 0 ? (int)$val : round($val, 1)) . ' ' . $units[$pow];
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

    private static function decodeHref(string $href): string
    {
        $href = html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return rawurldecode($href);
    }

    private static function decodeInputPath(string $value): string
    {
        if (function_exists('decodeBsomLegacyPath')) {
            return decodeBsomLegacyPath($value);
        }
        return str_replace(['&#38;', '&amp;'], '&', $value);
    }

    private static function normalizeIndexEntry(array $e): array
    {
        $originalRel = $e['rel'] ?? null;
        if (isset($e['rel']) && is_string($e['rel'])) {
            $e['rel'] = self::decodeInputPath($e['rel']);
        }
        if (isset($e['name']) && is_string($e['name'])) {
            $e['name'] = self::decodeInputPath($e['name']);
            if (!empty($e['isDir']) && $e['name'] !== '' && substr($e['name'], -1) !== '/') {
                $e['name'] .= '/';
            }
        }

        $relChanged = isset($e['rel']) && is_string($e['rel']) && $e['rel'] !== $originalRel;
        if (!$relChanged) {
            return $e;
        }

        $encoded = self::encodeRel($e['rel']);
        if (!empty($e['isDir'])) {
            $link = BASE_URL . '/bsom';
            if ($encoded !== '') {
                $link .= '?path=' . $encoded;
            }
            $e['link'] = $link;
            $e['viewUrl'] = $link;
            $e['downloadUrl'] = null;
        } else {
            $e['link'] = '';
            $e['viewUrl'] = BASE_URL . '/bsom/view?file=' . $encoded;
            $e['downloadUrl'] = BASE_URL . '/actions/bsom-download.php?file=' . $encoded;
        }

        return $e;
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
                $decoded = self::decodeHref($href);
                $decoded = str_starts_with($href, '/') ? $decoded : self::decodeHref((string)parse_url($href, PHP_URL_PATH));
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
                $target = ($path === '') ? self::decodeHref($href) : $path . '/' . self::decodeHref($href);
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
                $name = end($segs);
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
                $viewUrl = $link;
                $downloadUrl = null;
            } else {
                $viewUrl = BASE_URL . '/bsom/view?file=' . $encoded;
                $downloadUrl = BASE_URL . '/actions/bsom-download.php?file=' . $encoded;
            }

            $rows[] = [
                'name' => $name,
                'modified' => $modified,
                'size' => $size,
                'isDir' => $isDir,
                'isParent' => $isParent,
                'link' => $link,
                'viewUrl' => $viewUrl,
                'downloadUrl' => $downloadUrl,
                'rel' => $within,
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

    private const SEARCH_PARALLEL = 16;
    private const SEARCH_CACHE_TTL = 300;
    private const SEARCH_MAX_DEPTH = 5;
    private const SEARCH_MAX_REQUESTS = 2000;
    private const SEARCH_INDEX_STALE_AFTER = 86400;
    private const SEARCH_MAX_RESULTS = 200;
    private const INDEX_REBUILD_MAX_REQUESTS = 2000;
    private const INDEX_REBUILD_MAX_DEPTH = 5;
    private const INDEX_REBUILD_MAX_BATCH = 20;

    private static function fullIndexFile(): string
    {
        return sys_get_temp_dir() . '/bsom_full_index.json';
    }

    private static function lastIndexRebuiltAt(): ?int
    {
        $file = self::fullIndexFile();
        clearstatcache(true, $file);
        return is_file($file) ? filemtime($file) : null;
    }

    private static function loadFullIndex(): ?array
    {
        $file = self::fullIndexFile();
        if (!is_file($file)) {
            self::$fullIndexGeneratedAt = null;
            return null;
        }
        $data = json_decode((string)file_get_contents($file), true);
        if (!is_array($data) || !isset($data['entries']) || !is_array($data['entries'])) {
            self::$fullIndexGeneratedAt = null;
            return null;
        }
        self::$fullIndexGeneratedAt = isset($data['generated']) && is_numeric($data['generated'])
            ? (int)$data['generated']
            : filemtime($file);

        $entries = [];
        foreach ($data['entries'] as $entry) {
            if (is_array($entry)) {
                $entries[] = self::normalizeIndexEntry($entry);
            }
        }
        return $entries;
    }

    private static function fullIndexStale(): bool
    {
        if (self::$fullIndexGeneratedAt === null) {
            return true;
        }
        return (time() - self::$fullIndexGeneratedAt) > self::SEARCH_INDEX_STALE_AFTER;
    }

    private static function searchCacheFile(): string
    {
        return sys_get_temp_dir() . '/bsom_search_cache.json';
    }

    private static function readSearchCache(string $query, string $basePath): ?array
    {
        $file = self::searchCacheFile();
        if (!is_file($file)) {
            return null;
        }

        $data = json_decode((string)file_get_contents($file), true);
        if (!is_array($data) || !isset($data['generated']) || !is_numeric($data['generated'])) {
            return null;
        }
        if ((time() - (int)$data['generated']) > self::SEARCH_CACHE_TTL) {
            return null;
        }

        $hit = $data['queries'][$basePath][$query] ?? null;
        if (!is_array($hit) || !isset($hit['results']) || !is_array($hit['results'])) {
            return null;
        }

        return [
            'results' => $hit['results'],
            'stats' => [
                'requests' => 0,
                'folders' => 0,
                'maxDepth' => 0,
                'partial' => false,
                'fromSearchCache' => true,
            ],
        ];
    }

    private static function writeSearchCache(string $query, string $basePath, array $results): void
    {
        $file = self::searchCacheFile();
        $cache = [];

        if (is_file($file)) {
            $existing = json_decode((string)file_get_contents($file), true);
            if (is_array($existing)) {
                $cache = $existing;
            }
        }

        if (!isset($cache['generated']) || !is_numeric($cache['generated']) ||
            (time() - (int)$cache['generated']) > self::SEARCH_CACHE_TTL) {
            $cache = ['generated' => time(), 'queries' => []];
        }

        $cache['generated'] = time();
        $cache['queries'][$basePath][$query] = ['results' => $results];

        $tmp = $file . '.' . getmypid() . '.tmp';
        if (@file_put_contents($tmp, json_encode($cache, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false) {
            @rename($tmp, $file);
        } else {
            @unlink($tmp);
        }
    }

    private static function searchBsom(string $query, string $basePath): array
    {
        // If full index exists and searching from root, use it for instant global search
        if ($basePath === '') {
            $fullIndex = self::loadFullIndex();
            if ($fullIndex !== null) {
                $res = self::searchInIndex($query, $fullIndex);
                if (!empty($res['results'])) {
                    return $res;
                }
                if (!self::fullIndexStale()) {
                    return $res;
                }
                // Continue with live crawl only when the cached index is stale, so
                // files/folders added after the cached index can still be discovered.
            }
        }

        // Fallback: live crawl from basePath
        $cached = self::readSearchCache($query, $basePath);
        if ($cached !== null) {
            return $cached;
        }

        $crawl = self::crawlDirectoryTree(
            $basePath,
            function ($e) use ($query) {
                return stripos((string)($e['name'] ?? ''), $query) !== false ||
                    stripos((string)($e['rel'] ?? ''), $query) !== false;
            },
            self::SEARCH_MAX_RESULTS,
            self::SEARCH_MAX_DEPTH,
            self::SEARCH_MAX_REQUESTS
        );

        $results = $crawl['results'];
        $stats = $crawl['stats'];

        if (!$stats['partial']) {
            self::writeSearchCache($query, $basePath, $results);
        }

        return ['results' => $results, 'stats' => $stats];
    }

    private static function crawlDirectoryTree(
        string $basePath,
        ?callable $matcher = null,
        int $maxResults = 0,
        int $maxDepth = 0,
        int $maxRequests = 0
    ): array {
        $maxDepth = $maxDepth > 0 ? $maxDepth : self::SEARCH_MAX_DEPTH;
        $maxRequests = $maxRequests > 0 ? $maxRequests : self::SEARCH_MAX_REQUESTS;

        $results = [];
        $visited = [];
        $queue = [['rel' => $basePath, 'depth' => 0]];
        $stats = ['requests' => 0, 'folders' => 0, 'maxDepth' => 0, 'partial' => false];

        $maxExec = (int)ini_get('max_execution_time');
        if ($maxExec > 0) {
            $budget = min(18.0, $maxExec * 0.8);
        } else {
            $budget = 18.0;
        }
        $budget = max(6, $budget);
        $deadline = microtime(true) + $budget;

        $mh = curl_multi_init();
        $tasks = [];
        $nextId = 0;
        $active = true;

        $dispatch = function (string $rel, int $depth) use (&$tasks, &$nextId, &$visited, $mh) {
            if (isset($visited[$rel])) {
                return;
            }
            $visited[$rel] = true;
            $url = self::BSOM_SOURCE . self::encodeRel($rel);
            if (substr($url, -1) !== '/') {
                $url .= '/';
            }
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_HTTPHEADER => ['Accept: text/html'],
            ]);
            $id = ++$nextId;
            $tasks[$id] = ['ch' => $ch, 'rel' => $rel, 'depth' => $depth];
            curl_multi_add_handle($mh, $ch);
        };

        $dispatch($basePath, 0);

        $stop = false;
        do {
            $mrc = curl_multi_exec($mh, $active);
            while ($mrc === CURLM_CALL_MULTI_PERFORM) {
                $mrc = curl_multi_exec($mh, $active);
            }

            while (count($tasks) < self::SEARCH_PARALLEL && $queue !== []) {
                $it = array_shift($queue);
                $dispatch($it['rel'], $it['depth']);
            }

            $limitReached = $maxResults > 0 && count($results) >= $maxResults;
            if ($limitReached ||
                $stats['requests'] >= $maxRequests ||
                microtime(true) >= $deadline) {
                $stats['partial'] = count($queue) > 0 || count($tasks) > 0 ||
                    (!$limitReached && count($results) < $maxResults &&
                     ($stats['requests'] >= $maxRequests || microtime(true) >= $deadline));
                $stop = true;
            }

            if ($active) {
                @curl_multi_select($mh, 0.3);
            }

            while (($info = curl_multi_info_read($mh)) !== false) {
                $done = $info['handle'];
                $tid = null;
                foreach ($tasks as $k => $t) {
                    if ($t['ch'] === $done) {
                        $tid = $k;
                        break;
                    }
                }
                if ($tid === null) {
                    continue;
                }

                $task = $tasks[$tid];
                unset($tasks[$tid]);
                $html = curl_multi_getcontent($done);
                curl_multi_remove_handle($mh, $done);
                curl_close($done);
                $stats['requests']++;
                $stats['folders']++;
                if ($task['depth'] > $stats['maxDepth']) {
                    $stats['maxDepth'] = $task['depth'];
                }

                if ($html === false) {
                    continue;
                }

                $entries = self::parse($html, $task['rel']);
                foreach ($entries as $e) {
                    if ($e['isParent']) {
                        continue;
                    }
                    if ($matcher === null || $matcher($e)) {
                        $results[$e['rel'] . '#' . $e['name']] = $e;
                        if ($maxResults > 0 && count($results) >= $maxResults) {
                            $stats['partial'] = true;
                        }
                    }
                    if ($e['isDir'] && $task['depth'] < $maxDepth && !isset($visited[$e['rel']])) {
                        $queue[] = ['rel' => $e['rel'], 'depth' => $task['depth'] + 1];
                    }
                }
            }
        } while (($active || $queue !== [] || $tasks !== []) && !$stop);

        foreach ($tasks as $t) {
            curl_multi_remove_handle($mh, $t['ch']);
            curl_close($t['ch']);
        }
        curl_multi_close($mh);

        $results = array_values($results);
        usort($results, function ($a, $b) {
            $da = ($a['isDir'] ? 0 : 1);
            $db = ($b['isDir'] ? 0 : 1);
            if ($da !== $db) {
                return $da - $db;
            }
            return strnatcasecmp($a['name'], $b['name']) ?: strcmp($a['rel'], $b['rel']);
        });

        return ['results' => $results, 'stats' => $stats];
    }

    private static function rebuildLockFile(): string
    {
        return sys_get_temp_dir() . '/bsom_rebuild.lock';
    }

    private static function rebuildIndexScope(string $path): array
    {
        $path = rtrim(self::normalizePath($path), '/');
        if (strpos($path, '..') !== false) {
            return ['ok' => false, 'message' => 'Folder tidak valid.', 'stats' => null];
        }

        $lock = fopen(self::rebuildLockFile(), 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            return ['ok' => false, 'message' => 'Rebuild index sedang berjalan. Coba lagi beberapa saat.', 'stats' => null];
        }

        try {
            $crawl = self::crawlDirectoryTree($path, null, 0, self::INDEX_REBUILD_MAX_DEPTH, self::INDEX_REBUILD_MAX_REQUESTS);
            $stats = $crawl['stats'];

            if (empty($crawl['results'])) {
                return ['ok' => false, 'message' => 'Tidak ada entry yang bisa di-index dari folder tersebut.', 'stats' => $stats];
            }

            if ($path === '' && !empty($stats['partial'])) {
                return ['ok' => false, 'message' => 'Rebuild root terhenti karena batas crawl. Index tidak diubah.', 'stats' => $stats];
            }

            $existing = self::loadFullIndex() ?? [];
            $merged = [];

            foreach ($existing as $e) {
                $rel = isset($e['rel']) ? rtrim((string)$e['rel'], '/') : '';
                if ($path !== '' && ($rel === $path || strpos($rel, $path . '/') === 0)) {
                    continue;
                }
                $key = $rel !== '' ? $rel . '#' . ($e['name'] ?? '') : uniqid('bsom_', true);
                $merged[$key] = $e;
            }

            foreach ($crawl['results'] as $e) {
                $rel = isset($e['rel']) ? rtrim((string)$e['rel'], '/') : '';
                $key = $rel . '#' . ($e['name'] ?? '');
                $merged[$key] = $e;
            }

            $file = self::fullIndexFile();
            $payload = [
                'generated' => time(),
                'count' => count($merged),
                'entries' => array_values($merged),
            ];
            $tmp = $file . '.' . getmypid() . '.tmp';
            $written = @file_put_contents($tmp, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false;
            if ($written) {
                $written = @rename($tmp, $file);
                if ($written) {
                    clearstatcache(true, $file);
                }
            }
            if (!$written) {
                @unlink($tmp);
                return ['ok' => false, 'message' => 'Gagal menyimpan index.', 'stats' => $stats];
            }

            $message = 'Index folder ' . ($path !== '' ? $path : 'root') . ' diperbarui: ' .
                count($crawl['results']) . ' entry baru, total ' . count($merged) . ' entry (' .
                $stats['requests'] . ' request).';

            return [
                'ok' => true,
                'message' => $message,
                'stats' => $stats,
                'newEntries' => count($crawl['results']),
                'totalEntries' => count($merged),
            ];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private static function searchInIndex(string $query, array $index): array
    {
        $results = [];
        $lower = strtolower($query);
        foreach ($index as $e) {
            if (stripos($e['name'], $query) !== false || stripos($e['rel'], $query) !== false) {
                $results[] = $e;
                if (count($results) >= self::SEARCH_MAX_RESULTS) {
                    break;
                }
            }
        }
        usort($results, function ($a, $b) {
            $da = ($a['isDir'] ? 0 : 1);
            $db = ($b['isDir'] ? 0 : 1);
            if ($da !== $db) return $da - $db;
            return strnatcasecmp($a['name'], $b['name']) ?: strcmp($a['rel'], $b['rel']);
        });
        return [
            'results' => $results,
            'stats' => ['requests' => 0, 'folders' => count($index), 'maxDepth' => 0, 'partial' => false, 'fromIndex' => true]
        ];
    }

    private static function groupBySeason(array $results): array
    {
        $groups = [];
        foreach ($results as $e) {
            if ($e['isDir']) continue; // only files
            $rel = $e['rel'];
            $parts = explode('/', $rel);
            $season = $parts[0] ?? 'Lainnya';
            if (!isset($groups[$season])) {
                $groups[$season] = [];
            }
            $groups[$season][] = $e;
        }
        // sort seasons by name, Z to A
        uksort($groups, function ($a, $b) {
            return strnatcasecmp($b, $a);
        });
        // sort files within each season by name
        foreach ($groups as &$files) {
            usort($files, function ($a, $b) {
                return strnatcasecmp($a['name'], $b['name']);
            });
        }
        return $groups;
    }
}
