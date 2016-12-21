<?php
require_once(__DIR__.'/../../common.php');

// Get person info by querying by latest IP
$get_visitors = $db->prepare("
	SELECT mac, hostname, nick
	FROM visit v
	LEFT JOIN user u ON (SELECT id
	                     FROM user_mac m
	                     WHERE m.mac=v.mac AND m.changed<leave
	                     ORDER BY m.changed DESC LIMIT 1
	                    )=u.id
	WHERE ip=?
	ORDER BY leave DESC
	LIMIT 1
");

$ip = $_SERVER['REMOTE_ADDR'];
$data =
    db_execute($get_visitors, [$ip])->fetchArray(SQLITE3_ASSOC) ?:
    ["error" => "You are outside the lab network ($ip)"];

header("Content-Type: application/json; charset=utf-8");
print(json_encode($data)."\n");
