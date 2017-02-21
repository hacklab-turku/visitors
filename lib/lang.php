<?php
class LangFinnishHacklab {
    public static $chan = '#hacklab.jkl';
    
    public static function leave_line($a) {
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


    public static function join_line($a) {
        return "Ensimmäisenä saapui ".$a['nick'];
    }

    public static function evening_start($visits) {
        if (count($visits) === 0) {
            $msg = "Joku painoi \"kerhoilta alkaa\"-painiketta. Kuka olet?";
        } else {
            $msg = "Kerhoilta alkoi, paikalla ";
            $msg .= count($visits) > 1 ? 'ovat ' : 'on ';
            foreach ($visits as $user) {
            $msg .=
                $user['nick'].' (saapui '.
                date('H:i', $user['enter']).
                '), ';
            }
            $msg = substr($msg, 0, -2); // Remove comma+space
            $msg .= ". Tervetuloa!";
        }
        return $msg;
    }

    public static function button($e) {
        switch ($e) {
        case 'rtl_error':
            return lang_out('Softaradio meni sekaisin. :-/ Voisiko joku ottaa sen mustan tikun irti reitittimestä ja laittaa takaisin?');
        case 'rtl_ok':
            return lang_out('Kiitos, softaradio toimii taas. <3');
        default:
            switch ($e->model) {
            case 'Generic Remote':
                // Skip if not our remote and if button is released.
                if ($e->released || $e->chan !== 0) break;

                // Now parsing the events for buttons
                if ($e->button === 0 && $e->pressed) {
                    exec('sudo systemctl start qra');
                    return lang_out('Hacklabin valot syttyivät!');
                } else if ($e->button === 0 && !$e->pressed) {
                    exec('sudo systemctl stop qra');
                    return lang_out('Hacklabin valot sammuivat!');
                } else if ($e->button === 2 && $e->pressed) {
                    return lang_out('Nyt on eeppistä settiä! :-O');
                } else if ($e->button === 3 && $e->pressed) {
                    global $dhcp_lease_secs;
                    // Search current visitors
                    $req = [
                        'lease' => $dhcp_lease_secs,
                        'now' => time(),
                    ];
                    return lang_out(self::evening_start(get_visitors($req)));
                } else if ($e->button === 3 && !$e->pressed) {
                    return lang_out("Labilta ollaan tekemässä lähtöä...");
                }
                break;
            case 'Generic temperature sensor 1':
                if ($e->id === 0 && $e->temperature_C == 0.000) {
                    return lang_out('Ding! Dong!', FALSE);
                }
            }
        }
    }
}
