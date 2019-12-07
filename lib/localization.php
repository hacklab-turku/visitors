<?php
require_once(__DIR__.'/common.php');

class LocalizationHacklabJkl {

    private $ch;
    private $espeak;

    public function __construct() {
        // Configure Matrix cURL handle
        $this->ch = curl_init();
        curl_setopt_array($this->ch, [
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_VERBOSE => TRUE,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        $this->espeak = popen("exec espeak-ng -v fi -p 60", "w");
    }

    function notice($msg, $dom = NULL) {
        global $conf;
        $url = $conf['matrix']['homeserver'] . '/_matrix/client/r0/rooms/' . urlencode($conf['matrix']['room']) . '/send/m.room.message/' . uniqid() . '?access_token=' . urlencode($conf['matrix']['token']);

        $payload = [
            'body'    => $msg,
            'msgtype' => 'm.notice',
        ];

        if ($dom !== NULL) {
            $payload += [
                'format' => 'org.matrix.custom.html',
                'formatted_body' => $dom->saveHTML(),
            ];
        }

        curl_setopt_array($this->ch, [
            CURLOPT_URL => $url,
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        curl_exec($this->ch);
    }

    function speak($msg) {
        // FIXME Escape SSML sequences
        fwrite($this->espeak, "$msg\n");
        fflush($this->espeak);
    }

    public function last_leave($a) {
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
        $this->notice(substr($msg, 0, -2)); // Remove comma+space
    }


    public function first_join($a) {
        $this->notice("Ensimmäisenä saapui ".$a['nick']);
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

    public function button($e) {
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
                    global $dhcp_lease_secs;
                    // Search current visitors
                    $this->evening_start(get_visitors([
                        'lease' => $dhcp_lease_secs,
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
