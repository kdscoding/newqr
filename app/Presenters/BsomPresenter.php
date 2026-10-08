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

        $query = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $query = mb_substr($query, 0, 200);

        $sortCol = isset($_GET['sort']) ? (string)$_GET['sort'] : 'name';
        $sortDir = isset($_GET['dir']) ? (string)$_GET['dir'] : 'asc';
        $sortCol = in_array($sortCol, ['name', 'modified', 'size'], true) ? $sortCol : 'name';
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'asc';

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
        ];

        if ($file === '' || strpos($file, '..') !== false) {
            $viewData['error'] = 'Berkas tidak valid.';
        } else {
            $url = self::BSOM_SOURCE . self::encodeRel($file);
            $data = self::fetchRaw($url, $viewData['mime'], $viewData['size']);
            if ($data === false) {
                $viewData['error'] = 'Gagal mengambil berkas dari server bsom.';
            } else {
                $viewData['name'] = basename(urldecode($file));
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

        include BASE_PATH . '/views/bsom-view.php';
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

    private static function fetchRaw(string $url, string &$mime, string &$size)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HEADER => false,
        ]);
        $data = curl_exec($ch);
        $errno = curl_errno($ch);
        $mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'application/octet-stream';
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($errno || $code !== 200 || $data === false) {
            return false;
        }
        $size = self::humanSize(strlen($data));
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

    private const SEARCH_PARALLEL = 8;
    private const SEARCH_MAX_DEPTH = 5;
    private const SEARCH_MAX_REQUESTS = 300;
    private const SEARCH_MAX_RESULTS = 200;

    private static function fullIndexFile(): string
    {
        return sys_get_temp_dir() . '/bsom_full_index.json';
    }

    private static function loadFullIndex(): ?array
    {
        $file = self::fullIndexFile();
        if (!is_file($file)) {
            return null;
        }
        $data = json_decode((string)file_get_contents($file), true);
        if (!is_array($data) || !isset($data['entries']) || !is_array($data['entries'])) {
            return null;
        }
        return $data['entries'];
    }

    private static function searchBsom(string $query, string $basePath): array
    {
        // If full index exists and searching from root, use it for instant global search
        if ($basePath === '') {
            $fullIndex = self::loadFullIndex();
            if ($fullIndex !== null) {
                return self::searchInIndex($query, $fullIndex);
            }
        }

        // Fallback: live crawl from basePath
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

            if (count($results) >= self::SEARCH_MAX_RESULTS ||
                $stats['requests'] >= self::SEARCH_MAX_REQUESTS ||
                microtime(true) >= $deadline) {
                $stats['partial'] = count($queue) > 0 || count($tasks) > 0 ||
                    (count($results) < self::SEARCH_MAX_RESULTS &&
                     ($stats['requests'] >= self::SEARCH_MAX_REQUESTS ||
                      microtime(true) >= $deadline));
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
                    if (!$e['isDir']) {
                        if (stripos($e['name'], $query) !== false || stripos($e['rel'], $query) !== false) {
                            $results[$e['rel'] . '#' . $e['name']] = $e;
                            if (count($results) >= self::SEARCH_MAX_RESULTS) {
                                $stats['partial'] = true;
                            }
                        }
                    }
                    if ($e['isDir'] && $task['depth'] < self::SEARCH_MAX_DEPTH && !isset($visited[$e['rel']])) {
                        $queue[] = ['rel' => $e['rel'], 'depth' => $task['depth'] + 1];
                    }
                }
            }
        } while ($active && !$stop);

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
        // sort seasons naturally
        uksort($groups, 'strnatcasecmp');
        // sort files within each season
        foreach ($groups as &$files) {
            usort($files, function ($a, $b) {
                return strnatcasecmp($a['name'], $b['name']);
            });
        }
        return $groups;
    }
}
