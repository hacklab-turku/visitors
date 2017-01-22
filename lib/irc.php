<?php

// Connect to the message queue
$irc_resource = fopen(getenv('HOME').'/ii/rajaniemi.freenode.net/in', 'w');

function to_irc($msg) {
    // TODO make this not hardcoded
    global $irc_resource;
    // FIXME msg escaping of newline, reading of return values etc.
    fwrite($irc_resource, "/notice #hacklab.jkl :$msg\n");
    fflush($irc_resource);
}
