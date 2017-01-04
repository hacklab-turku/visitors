<?php
require_once(__DIR__.'/../../../lib/common.php');

$get_nicks = $db->prepare("
    SELECT nick FROM user ORDER by nick
");

$nicks = db_execute($get_nicks);
$a = [];
while (($data = $nicks->fetchArray(SQLITE3_NUM))) {
    array_push($a, $data[0]);
}

header("Content-Type: application/json; charset=utf-8");
print(json_encode($a)."\n");
