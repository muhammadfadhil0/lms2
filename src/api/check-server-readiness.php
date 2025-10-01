<?php
// Check server readiness for Word import feature
header('Content-Type: application/json');

$requirements = [
    'zip' => extension_loaded('zip'),
    'gd' => extension_loaded('gd'),
    'xml' => extension_loaded('xml'),
    'libxml' => extension_loaded('libxml')
];

$ready = $requirements['zip'] && $requirements['xml'] && $requirements['libxml'];

echo json_encode([
    'ready' => $ready,
    'requirements' => $requirements,
    'message' => $ready ? 
        'Server siap untuk fitur import Word' : 
        'Server memerlukan PHP extensions: ' . implode(', ', array_keys(array_filter($requirements, function($v) { return !$v; })))
]);
?>