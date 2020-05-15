<?php
require_once(__DIR__.'/../common.php');
require_once(__DIR__.'/../matrix.php');
require_once(__DIR__.'/../visitors.php');

class Localization {

    private $matrix;
    private $espeak;

    public function __construct() {
        global $conf;
        $this->matrix = new Matrix($conf['matrix']['homeserver'], $conf['matrix']['token']);
        $this->espeak = popen("exec espeak-ng -v fi -p 60", "w");
    }

    function notice($msg, $dom = NULL) {
        global $conf;
        $this->matrix->notice($conf['matrix']['room'], $msg, $dom);
    }

    function speak($msg) {
        // FIXME Escape SSML sequences
        fwrite($this->espeak, "$msg\n");
        fflush($this->espeak);
    }

    function hacklab_is_empty_msg($a) {
        $msg = "Hacklabilta poistuttiin. Paikalla oli";
        $msg .= count($a) > 1 ? 'vat' : '';

        // Matrix HTML message
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->appendChild($dom->createTextNode($msg.":"));
        $table = $dom->createElement("ul");
        $msg .= " ";

        foreach ($a as $user => $visits) {
            $row = $dom->createElement("li");
            $row->appendChild($dom->createElement("strong",$user));

            $msg .= "$user (";
            $range = "";
            foreach($visits as $visit) {
                $range .=
                    date('H:i', $visit['enter']).
                    '–'.
                    date('H:i', $visit['leave']).
                    ', ';
            }
            // Add closing brace before comma+space the hard way
            $range = substr($range, 0, -2);

            $msg .= $range . '), ';
            $row->appendChild($dom->createTextNode(" ".$range));
            $table->appendChild($row);
        }
        $dom->appendChild($table);
        $this->notice(substr($msg, 0, -2), $dom); // Remove comma+space
    }

    public function last_leave($a) {
        // Not used anymore. Using lock notification via Hackbus.
    }

    public function first_join($a) {
        // Not used anymore. Using lock notification via Hackbus.
    }

    public function hackbus($event, $value) {
        switch ($event) {
        case 'arming_state':
            switch ($value[0]) {
            case 'Unarmed':
                $nick = $value[1];
                $msg = " saapui Hacklabille.";

                // Matrix HTML message
                $dom = new DOMDocument('1.0', 'UTF-8');
                $dom->appendChild($dom->createElement("strong", $nick));
                $dom->appendChild($dom->createTextNode($msg));

                $this->notice($nick.$msg, $dom);
                exec('sudo systemctl start aikamerkki.timer');
                break;
            case 'Armed':
                $this->hacklab_is_empty_msg(find_leavers($value[2]));
                exec('sudo systemctl stop aikamerkki.timer');
                break;
            }
            break;
        }
    }

    public function evening_start($visits) {
        if (count($visits) === 0) {
            $this->speak('Tunnistaudu ennen kuin kerhoilta voi alkaa!');
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
            $this->notice($msg);
            $this->speak('Kerhoilta aloitettu.');
        }
    }

    // Radio controlled button (433MHz)
    public function radio_button($e) {
        switch ($e) {
        case 'rtl_error':
            $this->notice('Softaradio meni sekaisin. :-/ Voisiko joku ottaa sen koneen takana olevan USB-radion irti ja laittaa takaisin?');
            return true;
        case 'rtl_ok':
            $this->notice('Kiitos, softaradio toimii taas. <3');
            return true;
        default:
            switch ($e->model) {
            case 'Generic Remote':
                // Skip if not our remote and if button is released.
                if ($e->released || $e->chan !== 0) break;

                // Now parsing the events for buttons
                if ($e->button === 0 && $e->on) {
                    $this->notice('Hacklabin valot syttyivät!');
                    exec('sispmctl -o 1 -o 2 -o 4');
                    sleep(4); // Let the amplifier to warm up
                    exec('sudo systemctl start qra');
                    exec('sudo /bin/chvt 1');
                } else if ($e->button === 0 && !$e->on) {
                    $this->notice('Hacklabin valot sammuivat!');
                    exec('sudo systemctl stop qra');
                    exec('sudo systemctl stop slayradio');
                    exec('sispmctl -f 2 -f 4');
                    $this->speak('Hei hei ja turvallista kotimatkaa!');
                    sleep(3);
                    exec('sispmctl -f 1');
                    exec('ssh shutdown-alarmpi');
                } else if ($e->button === 1 && $e->on) {
                    exec('sudo systemctl start slayradio');
                } else if ($e->button === 1 && !$e->on) {
                    exec('sudo systemctl stop slayradio');
                } else if ($e->button === 2 && $e->on) {
                    $this->notice('Nyt on eeppistä settiä! :-O');
                } else if ($e->button === 2 && !$e->on) {
                    $this->notice('Ydinsota syttyi. Lukekaa kaasunaamarilaukustanne löytyvät suojautumisohjeet!');
                } else if ($e->button === 3 && $e->on) {
                    global $merge_window_sec;
                    // Search current visitors
                    $this->evening_start(get_visitors([
                        'lease' => $merge_window_sec,
                        'now' => time(),
                    ]));
                } else if ($e->button === 3 && !$e->on) {
                    $this->notice('Labilta ollaan tekemässä lähtöä...');
                    $this->speak('Muistakaa siivota ennen lähtöä!');
                } else {
                    return false;
                }
                return true;
            case 'Generic temperature sensor 1':
                if ($e->id === 0 && $e->temperature_C == 0.000) {
                    $this->notice('Ding! Dong!');
                    return true;
                }
                return false;
            }
            return false;
        }
    }
}
