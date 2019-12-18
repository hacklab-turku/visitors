<?php
$dbfile = @$options['database'] ?: __DIR__.'/../db/db.sqlite';
$db = new SQLite3($dbfile);
$db->busyTimeout(2000);
$db->exec('PRAGMA journal_mode = wal');
$merge_window_sec = 600; // Double than real lease interval reduces flapping
$common_read_var = $db->prepare('SELECT value FROM state WHERE key=?');
$common_update_var = $db->prepare('UPDATE state SET value=? WHERE key=?');
$common_insert_var = $db->prepare('INSERT INTO state (value,key) VALUES (?,?)');

// Register database connection killer
register_shutdown_function (function() {
    global $db;
    $db->close();
});

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
// accepted). If time elapses, return FALSE, otherwise TRUE. If you
// want to wait infinitely, set $secs to NULL or INF.
function is_data_available($fd, $secs) {
    // Prepare fd lists
    $fd_read = [$fd];
    $fd_write = NULL;
    $fd_ex = NULL;

    // Prepare time
    if ($secs === NULL || $secs === INF) {
        $tv_sec = NULL;
        $tv_usec = NULL;
    } else {
        $tv_sec = floor($secs);
        $tv_usec = floor(1e6*($secs - $tv_sec));
    }

    // Go
    $out = stream_select($fd_read, $fd_write, $fd_ex, $tv_sec, $tv_usec);
    if ($out === FALSE) err("Unable to select()");
    return $out > 0;
}

class SqlVar {
    private $k;
    private $v;
    private $raw;

    public function __construct($k, $def) {
        global $common_read_var, $common_insert_var;
        $this->k = $k;
        $res = db_execute($common_read_var, [$k])->fetchArray(SQLITE3_NUM);
        if ($res === FALSE) {
            // Key not found, store default
            $this->v = $def;
            $this->raw = json_encode($this->v);
            db_execute($common_insert_var, [$this->raw, $this->k]);
        } else {
            // Key found, decode it
            $this->raw = $res[0];
            $this->v = json_decode($this->raw, TRUE);
        }
    }

    public function get() {
        return $this->v;
    }

    public function getRaw() {
        return $this->raw;
    }
    
    public function set($new_v) {
        global $common_update_var, $db;
        $this->v = $new_v;
        $this->raw = json_encode($this->v);
        db_execute($common_update_var, [$this->raw, $this->k]);
        if ($db->changes() !== 1) {
            err('Key "'.$this->k.'" not singleton in table "state"');
        }
    }
}
