<?php

// Connect to the message queue
$zmq_context = new ZMQContext();
$zmq_pub = new ZMQSocket($zmq_context, ZMQ::SOCKET_PUB);
$zmq_pub->connect("tcp://127.0.0.1:61124");
sleep(1); // Magic delay because the shitty bot is undocumented

function to_irc($msg) {
    // TODO make this not hardcoded
    global $zmq_pub;
    $zmq_pub->send(json_encode([
        "target" => "#hacklab.jkl",
        "command" => "PRIVMSG",
        "from_channel" => true,
        "connection" => "FreeNode",
        "payload" => $msg,
    ]));
}
