<?php
namespace xfree;
/**
 * Validator class
 */
class Validator {
    private static $instance = null;

    private function __construct() {
    }

    public static function getInstance() {
        if (self::$instance) {
            return self::$instance;
        }

        return self::$instance = new Validator();
    }

    /**
     * email validation
     *
     * @param string $email
     *
     * @return bool
     */
    public function email($email) {
        return $this->filter($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * ipv4/v6 validation
     *
     * @param string $ip
     *
     * @return bool
     */
    public function ip($ip) {
        return $this->filter($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * url validation
     *
     * @param string $url
     *
     * @return bool
     */
    public function url($url) {
        return $this->filter($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * check if the data is empty 
     *
     * @param string $data
     *
     * @return bool
     */
    public function blank($data) {
        return trim($data) == '';
    }

    /**
     * check if the data is not empty 
     *
     * @param string $data
     *
     * @return bool
     */
    public function required($data) {
        return $this->not_blank($data);
    }

    /**
     * check if the data is not empty 
     *
     * @param string $data
     *
     * @return bool
     */
    public function not_blank($data) {
        return !$this->blank($data);
    }

    /**
     * check if the data is true
     *
     * @param mixed $data
     *
     * @return bool  true for 1, true, on and yes, false otherwise
     */
    public function boolean($data) {
        return $this->filter($data, FILTER_VALIDATE_BOOLEAN) !== false;
    }

    /**
     * check if the data is string with some options 
     * avaliable options:
     *   min_length: minimum length of the string
     *   max_length: maximum length of the string
     *   in_array: list of strings in a array
     *
     * @param string $data
     * @param array $options
     *
     * @return bool
     */
    public function string($data, Array $options = array()) {
        if (!is_string($data)) {
            return false;
        }

        $length = function_exists('mb_strlen') ? mb_strlen($data) : strlen($data);
        if ($this->lessThan($length, $options, 'min_length')) {
            return 'min_length';
        } 
        if ($this->greaterThan($length, $options, 'max_length')) {
            return 'max_length';
        }

        if (isset($options['in_array']) && !in_array($data, $options['in_array'])) {
            return false;
        }

        return true;
    }

    /**
     * check if it's an number with some options 
     * avaliable options:
     *   min: minimum number 
     *   max: maximum number 
     *   in_array: list of numbers in a array
     *
     * @param mixed $number
     * @param array $options
     *
     * @return mixed true on success, false otherwise 
     */
    public function number($number, Array $options = array()) {
        if (!is_numeric($number)) {
            return false;
        }

        if ($this->lessThan($number, $options, 'min')) {
            return 'min';
        }
        if ($this->greaterThan($number, $options, 'max')) {
            return 'max';
        }

        if (isset($options['in_array']) && !in_array($number, $options['in_array'])) {
            return false;
        }

        return true;
    }

    /**
     * check if it's an integer with some options 
     * avaliable options:
     *   min: minimum integer 
     *   max: maximum integer 
     *   in_array: list of integer numbers in a array
     *
     * @param mixed $number
     * @param array $options
     *
     * @return mixed 
     */
    public function integer($number, Array $options = array()) {
        $isNumber = $this->number($number, $options);
        return $isNumber === true ? is_integer(0 + $number) : $isNumber;
    }

    /**
     * check if it's an float with some options 
     * avaliable options:
     *   min: minimum float 
     *   max: maximum float 
     *   in_array: list of float numbers in a array
     *
     * @param mixed $number
     * @param array $options
     *
     * @return mixed 
     */
    public function float($number, Array $options = array()) {
        $isNumber = $this->number($number, $options);
        return $isNumber === true ? is_float(0 + $number) : $isNumber;
    }

    /**
     * check if the data matches the given regexp
     *
     * @param string $data
     * 
     * @return bool
     */
    public function regexp($data, $regexp) {
        return $this->filter(
            $data, 
            FILTER_VALIDATE_REGEXP,
            array(
                'options' => array(
                    'regexp' => $regexp
                )
            )
        ) !== false;
    }

    /**
     * filter variable, more: http://php.net/filter_var
     *
     * @param string $data
     * @param int $flag
     * @param array $options
     *
     * @return mixed
     */
    protected function filter($data, $flag, $options = array()) {
        return filter_var($data, $flag, $options);
    }

    /**
     * less than checking
     *
     * @param mixed $length
     * @param array $options
     * @param string $field
     *
     * @return bool  true if $options[$field] is not set or $length less than $options[$field], false otherwise
     */
    protected function lessThan($length, Array $options, $field) {
        return isset($options[$field]) && $length < $options[$field];
    }

    /**
     * greater than checking
     *
     * @param mixed $length
     * @param array $options
     * @param string $field
     *
     * @return bool  true if $options[$field] is not set or $length greater than $options[$field], false otherwise
     */
    protected function greaterThan($length, $options, $field) {
        return isset($options[$field]) && $length > $options[$field];
    }
}
