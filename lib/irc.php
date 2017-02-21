<?php

// Connect to the message queue
$irc_resource = fopen(getenv('HOME').'/ii/rajaniemi.freenode.net/in', 'w');

function to_irc($msg, $chan) {
    // TODO make this not hardcoded
    global $irc_resource;
    // FIXME msg escaping of newline, reading of return values etc.
    fwrite($irc_resource, "/notice $chan :$msg\n");
    fflush($irc_resource);
}

function lang_out($msg = NULL, $save_state = TRUE) {
    return (object)[
        'irc' => $msg,
        'save_state' => $save_state,
    ];
}
