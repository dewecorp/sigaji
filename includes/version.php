<?php
$vRaw = @exec('git log -1 --format=%ct 2>nul', $vOut, $vCode);
if ($vCode === 0 && ($vT = trim($vOut[0] ?? '')) && $vT !== '') {
    define('APP_VERSION', date('ynjGis', (int)$vT));
} elseif (file_exists($vFile = __DIR__ . '/../.version') && ($vC = trim(file_get_contents($vFile))) && $vC !== '') {
    define('APP_VERSION', date('ynjGis', (int)$vC));
} else {
    define('APP_VERSION', date('ynjGis', (int)@filemtime(__FILE__)));
}
