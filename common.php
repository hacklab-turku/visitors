<?php
$db = new SQLite3(__DIR__.'/db/db.sqlite');
$db->busyTimeout(2000);

function err($msg) {
    error_log($msg);
    exit(1);
}

// Executes given database query. Terminates in case of a database
// error. When $error === NULL then errors are passed though to
// caller.
function db_execute(&$stmt, $values = [], $error = "Database error") {
    global $db;

    // Prepare statement for reuse
    $stmt->reset();
    
    // Bind values
    foreach ($values as $k=>$v) {
        // Numeric indices start from 1, increment needed
        if (!is_string($k)) $k++;
        $stmt->bindValue($k, $v);
    }

    // Execute and check result
    $ret = $stmt->execute();
    if ($error !== NULL && $ret === FALSE) err($error);
    return $ret;
}

// Wait data from a stream for a given number of seconds (floats
// accepted). If time elapses, return FALSE, otherwise TRUE.
function is_data_available($fd, $secs) {
    // Prepare fd lists
    $fd_read = [$fd];
    $fd_write = NULL;
    $fd_ex = NULL;

    // Prepare time
    if ($secs === NULL) {
        $tv_sec = NULL;
        $tv_usec = NULL;
    } else {
        $tv_sec = floor($secs);
        $tv_usec = floor(1e6*($secs - $tv_sec));
    }

    // Go
    $out = stream_select($fd_read, $fd_write, $fd_ex, $tv_sec, $tv_usec);
    if ($out < 0) err("Unable to select()");
    return $out !== 0;
}
