<?php

$options = parse_ini_file(__DIR__.'/../../../hackbus.conf', TRUE);
if ($options === FALSE) {
    print("Configuration file invalid\n");
    exit(1);
}

function fail($msg) {
    print(json_encode(['error' => $msg])."\n");
    exit(1);
}

header('Content-Type: application/json; charset=utf-8');

if (!array_key_exists('read', $_GET)) fail("Parameter 'read' not specified");
$reads = explode(',', $_GET['read']);

// Clean up fields
foreach($reads as &$a) {
    $a = trim($a);
}

$req = [
    "method" => "r",
    "params" => $reads
];

$bus = stream_socket_client($options['hackbus']);
fwrite($bus, json_encode($req)."\n");
print(fgets($bus));
fclose($bus);
