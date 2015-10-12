<?php


if ( !function_exists('reverse_site_url')){
    function reverse_site_url($uri='') {
        $ci =& get_instance();
        return $ci->router->reverse_site_url($uri);
    }
}

if ( !function_exists('admin_url')){
    function admin_url($uri='') {
        $ci =& get_instance();

        if (empty($uri)) {
            return reverse_site_url($ci->mdi->config('admin_controller'));
        }

        return reverse_site_url($ci->mdi->config('admin_controller').'/'.$uri);
    }
}
