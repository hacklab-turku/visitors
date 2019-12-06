<?php
require_once(__DIR__.'/common.php');

// Universal visitor fetching query. When not searching the current
// visitors, set :lease to 0 because we already know the leave date if
// we are dealing with historic data.

// FIXME make it a class
$visitors_stmt = $db->prepare("
    SELECT nick, min(enter) as enter
    FROM visit v
    JOIN user u ON (SELECT id
                    FROM user_mac m
                    WHERE m.mac=v.mac AND changed<leave AND u.flappiness<=v.renewals
                    ORDER BY changed DESC
                    LIMIT 1
                   )=u.id
    WHERE enter<=:now AND leave>:now-:lease
    GROUP BY u.id
    ORDER BY nick
");

// Return result set with visitors
function get_visitors($args) {
    global $visitors_stmt;
    $res = db_execute($visitors_stmt, $args);
    $a = [];
    while (($data = $res->fetchArray(SQLITE3_ASSOC))) {
        array_push($a, $data);
    }
    return $a;
}
