<?php


if ( !function_exists('is_assoc')){
    function is_assoc(&$array) {
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }
}

if ( !function_exists('is_all_numeric')){
    function is_numeric_array(&$array) {
        if (!is_array($array)) {
            return false;
        }

        $result = true;
        foreach($array as $val){
            if(!is_numeric($val)){
                $result = false;
                break;
            }
        }

        return $result;
    }
}

if ( !function_exists('array_get')){
    function array_get(&$var, $default=null) {
        return isset($var) ? $var : $default;
    }
}
