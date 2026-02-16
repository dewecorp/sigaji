<?php
require_once __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Writer\PngWriter;

$data = $_GET['data'] ?? '';

if ($data === '') {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Missing data';
    exit;
}

try {
    $result = Builder::create()
        ->writer(new PngWriter())
        ->data($data)
        ->encoding(new Encoding('UTF-8'))
        ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
        ->size(180)
        ->margin(0)
        ->build();

    header('Content-Type: image/png');
    echo $result->getString();
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'QR error';
}
