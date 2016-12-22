<?php
require_once(__DIR__.'/../../common.php');

// Here are the individual SQL queries for each phase for clarity and
// below it is a mash-up of them for effectiveness
//
// SELECT mac FROM visit WHERE ip=:ip ORDER BY leave DESC LIMIT 1;
// SELECT id,changed FROM user_mac WHERE mac=:mac AND changed<:leave ORDER BY changed DESC LIMIT 1;
// SELECT nick FROM user WHERE id=:id

// Get person info by querying by latest IP
$get_user = $db->prepare("
	SELECT v.mac, hostname, nick, changed
	FROM visit v
	LEFT JOIN user_mac m ON (SELECT rowid
	                         FROM user_mac
	                         WHERE mac=v.mac AND changed<v.leave
	                         ORDER BY changed DESC
	                         LIMIT 1
	                        )=m.rowid
	LEFT JOIN user u ON m.id=u.id
	WHERE ip=?
	ORDER BY leave DESC
	LIMIT 1
");

$ip = $_SERVER['REMOTE_ADDR'];
$data =
    db_execute($get_user, [$ip])->fetchArray(SQLITE3_ASSOC) ?:
    ["error" => "You are outside the lab network ($ip)"];

header("Content-Type: application/json; charset=utf-8");
print(json_encode($data)."\n");
