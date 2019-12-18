<?php
require_once(__DIR__.'/../../../lib/common.php');

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

// Insert device by UID. This can be used for deleting as well when :id is NULL
$insert_by_uid = $db->prepare("
	INSERT INTO user_mac (id, mac, changed)
	SELECT :id, mac, :now
	FROM visit
	WHERE ip=:ip
	ORDER BY leave DESC
	LIMIT 1
");
$insert_by_uid->bindValue('now', time()-$merge_window_sec);

// Find user ID by nick
$find_uid = $db->prepare("
	SELECT id FROM user WHERE nick=?
");

// Insert user ID if possible
$insert_user = $db->prepare("
	INSERT INTO user (nick)
	VALUES (?)
");

$ip = $_SERVER['REMOTE_ADDR'];
$outerror = [
    "error" => "You are outside the lab network ($ip)",
    "errorcode" => "OUT"
];
$nickmissing = [
    "error" => "You must specify your nickname as parameter 'nick'",
    "errorcode" => "EMPTY"
];

switch ($_SERVER['REQUEST_METHOD']) {
case 'GET':
    // Allow IP queries for everybody
    if (array_key_exists('ip', $_GET)) {
        $o = db_execute($get_user, [$_GET['ip']])->fetchArray(SQLITE3_ASSOC) ?: $outerror;
        unset($o['mac']);
        unset($o['changed']);
    } else {
        $o = db_execute($get_user, [$ip])->fetchArray(SQLITE3_ASSOC) ?: $outerror;
    }
    break;
case 'DELETE':
    db_execute($insert_by_uid, [
        'id'  => NULL,
        'ip'  => $ip
    ]);
    $o = $db->changes() === 1 ? ["success" => TRUE] : $outerror;
    break;
case 'PUT':
    if (!array_key_exists('nick', $_GET) || $_GET['nick'] === '') {
        $o = $nickmissing;
        break;
    }

    $db->exec('BEGIN');
    $row = db_execute($find_uid, [$_GET['nick']])->fetchArray(SQLITE3_ASSOC);
    if ($row === FALSE) {
        db_execute($insert_user, [$_GET['nick']]);
        $uid = $db->lastInsertRowid();
    } else {
        $uid = $row['id'];
    }

    // We know uid by now, let's insert
    db_execute($insert_by_uid, [
        'id'  => $uid,
        'ip'  => $ip
    ]);

    if ($db->changes() === 1) {
        $o = ["success" => TRUE];
        $db->exec('END');
    } else {
        $o = $outerror;
        // Do not allow trolling outside the lab network
        $db->exec('ROLLBACK');
    }
    break;
default:
    $o = ["error" => "Unsupported method"];
}
    
header('Content-Type: application/json; charset=utf-8');
print(json_encode($o)."\n");
