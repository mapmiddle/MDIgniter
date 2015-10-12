<?php


if ( !function_exists('is_request_method')){
    function is_request_method($method) {
        return strtoupper($method) == strtoupper($_SERVER['REQUEST_METHOD']);
    }
}
