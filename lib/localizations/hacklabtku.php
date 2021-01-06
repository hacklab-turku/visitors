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

            if ($hours == 0) {
                $msg .= $minutes .' minutes.';
            }else {
                $msg .= $hours .' hours and ' . $minutes . '.';
            }

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

    public function hackbus($event, $value) {
        // empty
    }
}
