<?php
define('BASE_URL','/newqr');
define('BASE_PATH','D:/server/laragon/www/newqr');
require BASE_PATH.'/app/Presenters/BsomPresenter.php';

echo "=== FULL BSOM INDEX BUILDER ===\n";
echo "Target: http://10.10.10.98/bsom/\n";
echo "Estimated: 13,000+ folders, several hours.\n";
echo "Press Ctrl+C to stop anytime.\n\n";

$rc = new ReflectionClass('BsomPresenter');
$parse = $rc->getMethod('parse'); $parse->setAccessible(true);
$encodeRel = $rc->getMethod('encodeRel'); $encodeRel->setAccessible(true);
$bsomSource = $rc->getConstant('BSOM_SOURCE');

$mh = curl_multi_init();
$tasks = []; $nextId = 0; $visited = []; $queue = []; $flat = [];
$active = true;
$startTime = microtime(true);
$lastReport = $startTime;
$totalRequests = 0;

$dispatch = function (string $rel, int $depth) use (&$tasks, &$nextId, &$visited, $mh, $encodeRel, $bsomSource) {
    if (isset($visited[$rel])) { return; }
    $visited[$rel] = true;
    $url = $bsomSource . $encodeRel->invoke(null, $rel) . '/';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Accept: text/html'],
    ]);
    $id = ++$nextId;
    $tasks[$id] = ['ch' => $ch, 'rel' => $rel, 'depth' => $depth];
    curl_multi_add_handle($mh, $ch);
};

$dispatch('', 0);
$reportInterval = 30; // seconds

while ($active || $queue !== []) {
    // refill
    while (count($tasks) < 12 && $queue !== []) {
        $it = array_shift($queue);
        $dispatch($it['rel'], $it['depth']);
    }
    
    $mrc = curl_multi_exec($mh, $active);
    while ($mrc === CURLM_CALL_MULTI_PERFORM) {
        $mrc = curl_multi_exec($mh, $active);
    }
    
    if ($active) {
        @curl_multi_select($mh, 0.2);
    }
    
    while (($info = curl_multi_info_read($mh)) !== false) {
        $done = $info['handle'];
        $tid = null;
        foreach ($tasks as $k => $t) { if ($t['ch'] === $done) { $tid = $k; break; } }
        if ($tid === null) continue;
        $task = $tasks[$tid]; unset($tasks[$tid]);
        $html = curl_multi_getcontent($done);
        curl_multi_remove_handle($mh, $done); curl_close($done);
        $totalRequests++;
        
        if ($html === false) continue;
        
        $entries = $parse->invoke(null, $html, $task['rel']);
        foreach ($entries as $e) {
            if ($e['isParent']) continue;
            $flat[] = $e;
            if ($e['isDir'] && !isset($visited[$e['rel']])) {
                $queue[] = ['rel' => $e['rel'], 'depth' => $task['depth'] + 1];
            }
        }
    }
    
    // progress report
    $now = microtime(true);
    if ($now - $lastReport >= $reportInterval) {
        $elapsed = $now - $startTime;
        $rate = $totalRequests / max(1, $elapsed) * 3600;
        $eta = count($queue) > 0 ? (count($queue) / max(0.01, $rate) * 3600) : 0;
        echo sprintf("[%s] reqs=%d flat=%d queue=%d visited=%d rate=%.0f/hr eta=%.1fmin\n",
            date('H:i:s'), $totalRequests, count($flat), count($queue), count($visited), $rate, $eta);
        $lastReport = $now;
        
        // periodic save
        $cacheFile = sys_get_temp_dir() . '/bsom_full_index.json';
        $payload = json_encode(['generated' => time(), 'count' => count($flat), 'entries' => $flat]);
        @file_put_contents($cacheFile, $payload, LOCK_EX);
    }
}

curl_multi_close($mh);

// final save
$cacheFile = sys_get_temp_dir() . '/bsom_full_index.json';
$payload = json_encode(['generated' => time(), 'count' => count($flat), 'entries' => $flat]);
@file_put_contents($cacheFile, $payload, LOCK_EX);

$elapsed = microtime(true) - $startTime;
echo "\n=== DONE ===\n";
echo "Total: " . count($flat) . " entries, " . $totalRequests . " requests in " . round($elapsed/60,1) . " min\n";
echo "Cache: $cacheFile\n";