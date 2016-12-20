<?php
require_once(__DIR__.'/common.php');

// Universal visitor fetching query. When not searching the current
// visitors, set :lease to 0 because we already know the leave date if
// we are dealing with historic data.
$get_visitors = $db->prepare("
    SELECT nick, min(enter) as enter
    FROM visit v
    JOIN user_mac USING (mac)
    JOIN user u ON (SELECT id
                    FROM user_mac m
                    WHERE m.mac=v.mac AND changed<leave
                    ORDER BY changed DESC LIMIT 1
                   )=u.id
    WHERE enter<=:now AND leave>:now-:lease
    GROUP BY u.id
    ORDER BY nick
");

$visits = db_execute($get_visitors, [
    'lease' => $dhcp_lease_secs,
    'now' => gettimeofday(true)
]);

while (($data = $visits->fetchArray(SQLITE3_ASSOC))) {
    print($data['nick']." (saapui ".date('H:i', $data['enter']).")\n");
//    print_r($data);
}
