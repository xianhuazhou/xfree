<?php
namespace xfree;

/**
 * Log class
 */
class Logger {
    const INFO = 1;
    const WARNNING = 2;
    const DEBUG = 3;
    const NONE = 4;

    /**
     * add log
     *
     * @param int $level
     * @param string $info
     * @param string $eof
     *
     */
    public static function log($level, $info, $eof = "\n") {
        if (X::get('x.log_level') >= $level) {
            file_put_contents(X::get('log_file'), $info . $eof, FILE_APPEND);
        } 
    }
}
