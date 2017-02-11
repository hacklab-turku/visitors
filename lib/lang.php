<?php
function leave_line($a) {
    $msg = "Hacklab on nyt tyhjä. Paikalla oli";
    $msg .= count($a) > 1 ? 'vat ' : ' ';
    foreach ($a as $user => $visits) {
        $msg .= "$user (";
        foreach($visits as $visit) {
            $msg .=
                date('H:i', $visit['enter']).
                '–'.
                date('H:i', $visit['leave']).
                ', ';
        }
        // Add closing brace before comma+space the hard way
        $msg = substr($msg, 0, -2).'), ';
    }
    return substr($msg, 0, -2); // Remove comma+space
}

function join_line(&$a) {
    return "Ensimmäisenä saapui ".$a['nick'];
}
