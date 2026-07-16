<?php
$vRaw = @exec('git log -1 --format=%ct 2>nul', $vOut, $vCode);
if ($vCode === 0 && ($vT = trim($vOut[0] ?? '')) && $vT !== '') {
    define('APP_VERSION', date('ynjGis', (int)$vT));
} else {
    define('APP_VERSION', date('ynjGis', (int)@filemtime(__FILE__)));
}
