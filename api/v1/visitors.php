<?php
require_once(__DIR__.'/../../common.php');

// Universal visitor fetching query. When not searching the current
// visitors, set :lease to 0 because we already know the leave date if
// we are dealing with historic data.
$get_visitors = $db->prepare("
    SELECT nick, min(enter) as enter
    FROM visit v
    JOIN user u ON (SELECT id
                    FROM user_mac m
                    WHERE m.mac=v.mac AND changed<leave
                    ORDER BY changed DESC LIMIT 1
                   )=u.id
    WHERE enter<=:now AND leave>:now-:lease
    GROUP BY u.id
    ORDER BY nick
");

// Search with timestamp or current visitors
$req = array_key_exists('at', $_GET) ?
    [
        'lease' => 0,
        'now' => intval($_GET['at'])
    ] : [
        'lease' => $dhcp_lease_secs,
        'now' => gettimeofday(true)
    ];
$visits = db_execute($get_visitors, $req);

switch (@$_GET['format'] ?: 'text') {
case 'text':
    header("Content-Type: text/plain; charset=utf-8");
    while (($data = $visits->fetchArray(SQLITE3_ASSOC))) {
        print($data['nick']." (saapui ".date('H:i', $data['enter']).")\n");
    }
    break;
case 'iframe':
    $at_human = date('H:i', $req['now']);
    header("Content-Type: text/html; charset=utf-8");

    // Just implementing the previous HTML template even though it is
    // not valid.
    print("<html><body style='color:white'>");
    $msg = '';
    while (($data = $visits->fetchArray(SQLITE3_ASSOC))) {
        $msg .= $data['nick']."\n";
    }
    if ($msg == '') {
        print("Hacklabin WLANissa ei ole nyt ketään.<br />");
    } else {
        print("Hacklabin WLANissa nyt:<br /><b>\n$msg</b>");
    }
    print("<br />(päivitetty kello $at_human, ilmoita MAC-osoitteesi jpa:lle)</body></html>\n");
    break;
case 'json':
    header("Content-Type: application/json; charset=utf-8");
    $a = [];
    while (($data = $visits->fetchArray(SQLITE3_ASSOC))) {
        array_push($a, $data);
    }
    print(json_encode($a)."\n");
    break;
default:
    http_response_code(400);
    header("Content-Type: text/plain; charset=utf-8");
    print("Invalid format\n");
}
