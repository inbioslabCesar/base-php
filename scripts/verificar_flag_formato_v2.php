<?php
require_once __DIR__ . '/../src/examenes/formato_dinamico_helper.php';

$raw = getenv('LAB_FORMAT_V2_ENABLED');
$rawText = ($raw === false) ? '(no definido)' : (string)$raw;
$enabled = lab_format_v2_enabled() ? 'SI' : 'NO';

echo 'LAB_FORMAT_V2_ENABLED raw: ' . $rawText . PHP_EOL;
echo 'lab_format_v2_enabled(): ' . $enabled . PHP_EOL;
