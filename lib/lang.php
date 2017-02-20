<?php
class LangFinnishHacklab {
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
}
