<?php


if ( !function_exists('datetime_without_hours')){
    function datetime_without_hours($datetime) {
        return date('Y-m-d', strtotime($datetime));
    }
}