<?php
require_once(__DIR__.'/../../../lib/common.php');
require_once(__DIR__.'/../../../lib/visitors.php');

// This function is from http://stackoverflow.com/a/7153133/514723 by velcrow
function utf8($num)
{
    if($num<=0x7F)       return chr($num);
    if($num<=0x7FF)      return chr(($num>>6)+192).chr(($num&63)+128);
    if($num<=0xFFFF)     return chr(($num>>12)+224).chr((($num>>6)&63)+128).chr(($num&63)+128);
    if($num<=0x1FFFFF)   return chr(($num>>18)+240).chr((($num>>12)&63)+128).chr((($num>>6)&63)+128).chr(($num&63)+128);
    return '';
}

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

// Allow CORS
header('Access-Control-Allow-Origin: *');

switch (@$_GET['format'] ?: 'text') {
case 'widget':
    // People are reloading it anyway so let's serve them a playing
    // card :-)
    $suit = ["♠","♣","♥","♦"][rand(0,3)];
    $num = ["A","2","3","4","5","6","7","8","9","10","J","Q","K"][rand(0,12)];
    header("Content-Type: text/plain; charset=utf-8");
    if (empty($visits)) {
        print("Hacklab on tyhjä. $suit$num\n");
    } else {
        foreach ($visits as $data) {
            $hour = idate('h', $data['enter']) - 1 % 12;
            $min = idate('i', $data['enter']) >= 30 ? 12 : 0;
            $point = utf8(0x1F550 + $hour + $min);
            print($point." ".$data['nick']."\n");
        }
        print("$suit$num\n");
    }
    break;    
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
