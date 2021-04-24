<?php
require_once(__DIR__.'/../common.php');
require_once(__DIR__.'/../matrix.php');
require_once(__DIR__.'/../visitors.php');

class Localization {
    private $matrix;

    public function __construct() {
        global $conf;
        $this->matrix = new Matrix($conf['matrix']['homeserver'], $conf['matrix']['token']);
    }

    function notice($msg, $dom = NULL) {
        global $conf;
        $this->matrix->notice($conf['matrix']['room'], $msg, $dom);
    }

    public function last_leave($a) {
            $msg = "Hacklab is not occupied anymore. There ";
            $msg .= count($a) > 1 ? 'were ' : 'was ';
            $msg .= count($a);
            $msg .= ' ';
            $msg .= count($a) > 1 ? 'users.' : 'user.';
            $msg .= ' ';
            $msg .= count($a) > 1 ? ' Time spent labbing across all users ' : ' Time spent labbing ';

            $visitors = 0;
            $seconds = 0;
            foreach($a as $user => $visits) {
                $visitors++;
                foreach($visits as $visit) {
                    $seconds += $visit['leave'] - $visit['enter'];
                }
            }

            $hours = floor($seconds/3600);
            $minutes = floor(($seconds/60)%60);
            $days = floor(($seconds%2592000)/86400);

            if ($hours == 0) {
                $msg .= $minutes . ' minutes.';
            }elseif ($days >=1){
                $msg .= $days . ' days,' .$hours . ' hours and ' . $minutes . '.';
            }else {
                $msg .= $hours .' hours and ' . $minutes . ' minutes.';
            }

            #if ($this->get_light_status() != -1) {
            #    # Do nothing when the lights are on, but nobody is on Wifi.
            #} else {
                # Only send message when the lights are off, and last leaves from Wifi.
                $dom = new DOMDocument('1.0', 'UTF-8');
                $dom->appendChild($dom->createTextNode($msg));
                $this->notice($msg, $dom);
            #}
    }


    public function test_message() {
           $msg = "This is a manually invoked test message.";
           $dom = new DOMDocument('1.0', 'UTF-8');
           $dom->appendChild($dom->createTextNode($msg));
           $this->notice($msg, $dom);
    }

    public function first_join($a) {
        $msg = "The first lab user has arrived. ";

        // Matrix HTML message
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->appendChild($dom->createTextNode($msg));

        $this->notice($msg, $dom);
    }

    public function first_join_light() {
        $msg = "First lab user has arrived. This was determined by light sensor.";

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->appendChild($dom->createTextNode($msg));

        $this->notice($msg, $dom);
    }

    public function hackbus($event, $value) {
        // empty
    }

    public function get_light_status() {
        $response = $this->get_json_from_url("http://localhost/pi_api/gpio/?a=readPin&pin=1");
        if ($response['status'] == "OK") {
            return $response['data'];
        } else {
            return -1;
        }
    }

    public function get_json_from_url($url) {
        $json = file_get_contents($url);
        return json_decode($json, TRUE);
    }
}
