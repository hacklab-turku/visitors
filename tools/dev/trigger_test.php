#!/usr/bin/env php
<?php

//Run with --visitors or -v with JSON file containing the visitor data

require_once(__DIR__.'/../../lib/common.php');
require_once(__DIR__.'/../../lib/visitors.php');

$options = getopt('v:c:', [
    'visitors:',
    'config:',
]);

// Create new notifier.
$conffile = @$options['c'] ?: @$options['config'] ?: __DIR__.'/../../notifier.conf';
$conf = parse_ini_file($conffile, TRUE);
if ($conf === FALSE) {
    print("Configuration file invalid\n");
    exit(1);
}

// The actual query after all the boilerplate
require_once(__DIR__.'/../../lib/localizations/'.$conf['localization'].'.php');
$loc = new Localization();
$loc->first_join_light();