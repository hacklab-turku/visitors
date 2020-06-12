<?php

$options = parse_ini_file(__DIR__.'/../../../hackbus.conf', TRUE);
if ($options === FALSE) {
    print("Configuration file invalid\n");
    exit(1);
}

$bus = stream_socket_client($options['hackbus']);
fwrite($bus, '{"method":"r","params":["arming_state","in_charge"]}'."\n");
$state = json_decode(fgets($bus));
fclose($bus);

header("Content-Type: application/json; charset=utf-8");
print(json_encode($state->result->arming_state == "Armed" ? null : $state->result->in_charge)."\n");
