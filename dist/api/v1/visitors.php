<?php
require_once(__DIR__.'/../../../lib/common.php');
require_once(__DIR__.'/../../../lib/visitors.php');

// Search with timestamp or current visitors
$req = array_key_exists('at', $_GET) ?
    [
        'lease' => 0,
        'now' => intval($_GET['at'])
    ] : [
        'lease' => $dhcp_lease_secs,
        'now' => gettimeofday(true)
    ];
$visits = get_visitors($req);

switch (@$_GET['format'] ?: 'text') {
case 'text':
    header("Content-Type: text/plain; charset=utf-8");
    if (empty($visits)) {
        print("Hacklab on nyt tyhjä.\n");
    } else {
        foreach ($visits as $data) {
            print($data['nick']." (saapui ".date('H:i', $data['enter']).")\n");
        }
    }
    break;
case 'iframe':
    $at_human = date('H:i', $req['now']);
    header("Content-Type: text/html; charset=utf-8");

    // Just implementing the previous HTML template even though it is
    // not valid.
    print("<html><body style='color:white'>");
    $msg = '';
    foreach ($visits as $data) {
        $msg .= $data['nick']."\n";
    }
    if ($msg == '') {
        print("Hacklabin WLANissa ei ole nyt ketään.<br />");
    } else {
        print("Hacklabin WLANissa nyt:<br /><b>\n$msg</b>");
    }
    print("<br />(päivitetty klo $at_human) <a style=\"color: #66ffff;\" target=\"_blank\" href=\"../..\">Muuta tietojasi</a></body></html>\n");
    break;
case 'json':
    header("Content-Type: application/json; charset=utf-8");
    print(json_encode($visits)."\n");
    break;
default:
    http_response_code(400);
    header("Content-Type: text/plain; charset=utf-8");
    print("Invalid format\n");
}
