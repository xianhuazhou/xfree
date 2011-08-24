<?php
namespace xfree;

/**
 * Log class
 */
class Logger {
    const DEBUG = 1;
    const INFO = 2;
    const WARNNING = 3;
    const ERROR = 4;
    const NONE = 5;

    /**
     * add log
     *
     * @param int $level
     * @param string $info
     * @param string $eof
     * @param mixed $time
     *
     */
    public static function log($level, $info, $eof = "\n", $time = null) {
        $myLogLevel = X::get('x.log_level') ?: self::NONE;
        if ($myLogLevel <= $level) {
            if ($time == null) {
                $time = date('H:i:s');
            }
            file_put_contents(X::get('log_file'), $time . ' - ' . $info . $eof, FILE_APPEND);
        } 
    }

    /**
     * log level: info
     *
     * @param string $info
     * @param string $eof
     * @param mixed $time
     *
     */
    public static function info($info, $eof = "\n", $time = null) {
        self::log(self::INFO, $info, $eof, $time);
    }

    /**
     * log level: warnning 
     *
     * @param string $info
     * @param string $eof
     * @param mixed $time
     *
     */
    public static function warnning($info, $eof = "\n", $time = null) {
        self::log(self::WARNNING, $info, $eof, $time);
    }

    /**
     * log level: debug 
     *
     * @param string $info
     * @param string $eof
     * @param mixed $time
     *
     */
    public static function debug($info, $eof = "\n", $time = null) {
        self::log(self::DEBUG, $info, $eof, $time);
    }

    /**
     * log level: error 
     *
     * @param string $info
     * @param string $eof
     * @param mixed $time
     *
     */
    public static function error($info, $eof = "\n", $time = null) {
        self::log(self::ERROR, $info, $eof, $time);
    }
}
