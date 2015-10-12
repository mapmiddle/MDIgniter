<?php



if ( !function_exists('get_file_extension')){
    function get_file_extension($path) {
        if (($last_dot = strrpos($path, '.')) !== FALSE) {
            $ext = strtolower(substr($path, $last_dot + 1));
        } else {
            $ext = '';
        }

        return $ext;
    }
}

if ( !function_exists('array_ikey_exists')) {
    function array_ikey_exists($key, $search) {
        return array_key_exists(strtolower($key), array_change_key_case($search));
    }
}