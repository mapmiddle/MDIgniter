<?php


define('MDI_VERSION', '1.0.0');

require_once 'utils/mdi_arrays.php';
require_once 'utils/mdi_castings.php';
require_once 'utils/mdi_classes.php';
require_once 'utils/mdi_generators.php';
require_once 'utils/mdi_requests.php';
require_once 'utils/mdi_strings.php';
require_once 'utils/mdi_templates.php';
require_once 'utils/mdi_times.php';
require_once 'utils/mdi_urls.php';

class mdi {
    static $PATH_ROOT;
    static $PATH_APP;
    static $PATH_STATIC;
    static $ci;
    private static $config = array();
    private static $statics = array();
    private static $jqueries = array();
    private static $is_loaded = array();

    public static function pre_controller() {
        //blank
    }

    public static function controller_constructor() {
        mdi::$ci =& get_instance();

        // version check
        if (version_compare(phpversion(), '5.3.0') == -1) {
            mdi::error('PHP version is lower than 5.3');///
        }

        // initialize static variable
        mdi::$PATH_ROOT = APPPATH.'libraries/mdi/';
        mdi::$PATH_APP = mdi::$PATH_ROOT.'apps/';
        mdi::$PATH_STATIC = mdi::$PATH_ROOT.'statics/';

        // load config
        mdi::$ci->config->load('mdi/mdi', TRUE, TRUE);
        mdi::$config = mdi::$ci->config->item('mdi/mdi');

        // load package path
        mdi::$ci->load->add_package_path(mdi::$PATH_APP);

        // include default libraries
        require_once('libs/datamapper/datamapper.php');
        new DataMapper();

        // include default classes
        require_once('apps/class/mdi_model.php');

        // include default models
        require_once('apps/models/mdi_user.php');

        // timezone
        date_default_timezone_set(mdi::config('timezone'));
    }

    public static function get_user_model() {
        return MDI_USER_MODEL;
    }

    public static function config($option) {
        return mdi::$config[$option];
    }

    public static function is_debug() {
        return mdi::config('debug');
    }

    public static function error($message, $status_code=500) {
        self::$ci->load->library('user_agent');

        if (self::$ci->agent->is_mobile_app() || self::$ci->input->is_ajax_request()) {
            set_status_header($status_code);
            echo $message;
            exit;
        } else {
            show_error($message, $status_code);
            exit;
        }
    }

    public function __construct() {
        mdi::controller_constructor();
    }

    public function load($apps_name, $terminate=TRUE) {
        if (array_key_exists($apps_name, mdi::$is_loaded)) {
            return TRUE;
        }

        $filepath = strtolower(mdi::$PATH_APP.$apps_name.'.php');

        if (!file_exists($filepath)) {
            if ($terminate) {
                mdi::error("mdi::load Error - "."file not found"."(".$filepath.")");///
            }

            return FALSE;
        } else {
            $class = $apps_name;

            if (($last_slash = strrpos($class, '/')) !== FALSE) {
                // The path is in front of the last slash
                //$path = substr($model, 0, $last_slash + 1);

                // And the model name behind it
                $class = substr($class, $last_slash + 1);
            }

            /** @noinspection PhpIncludeInspection */
            include($filepath);

            if (class_exists($class)) {
                $varname = strtolower($class);
                $this->{$varname} = new $class();
            }

            mdi::$is_loaded[$apps_name] = TRUE;
        }

        return TRUE;
    }

    public function statics($static_file) {
        $ext = get_file_extension($static_file);
        if (!empty($ext)) {
            if (isset(mdi::$statics[$ext])) {
                if (isset(mdi::$statics[$ext][$static_file])) {
                    unset(mdi::$statics[$ext][$static_file]);
                }
            }
        }

        echo $this->_statics_tag($ext, $static_file);
    }

    public function base_url($uri='', $default_args=array(), $additional_args=array(), $delete_args=array()) {
        $url = base_url($uri);
        return $this->_url($url, $default_args, $additional_args, $delete_args);
    }

    public function statics_url($static_file) {
        echo $this->_statics_url($static_file);
    }

    public function media_url($media_file) {
        echo $this->_media_url($media_file);
    }

    public function statics_lazy($group, $static_file) {
        if (!isset(mdi::$statics[$group])) {
            mdi::$statics[$group] = array();
        }

        if (isset(mdi::$statics[$group][$static_file])) {
            return;
        }

        $tag = $this->_statics_tag($group, $static_file);
        if ($tag) {
            mdi::$statics[$group][$static_file] = $tag;
        }
    }

    public function statics_flush($group) {
        if (isset(mdi::$statics[$group])) {
            foreach(mdi::$statics[$group] as $tag) {
                echo $tag;
            }
        }

        mdi::$statics[$group] = array();
    }


    public function jquery($codes) {
        if (is_array($codes)) {
            mdi::$jqueries = array_merge(mdi::$jqueries, $codes);
        } else {
            mdi::$jqueries = array_merge(mdi::$jqueries, array($codes));
        }
    }

    public function jquery_flush() {
        if (!empty(mdi::$jqueries)) {
            echo "\n"."<script>"."\n";
            echo "$(document).ready(function(){\n";
            echo implode("\n", mdi::$jqueries)."\n";
            echo "});\n";
            echo "</script>"."\n";
        }
        mdi::$jqueries = array();
    }

    private function _statics_tag($group, $static_file) {
        $static_path = $this->_statics_url($static_file);
        switch($group) {
            case 'css':
                return '<link href="'.$static_path.'" rel="stylesheet">';
                break;

            case 'js':
                return '<script src="'.$static_path.'"></script>';
                break;

            default:
            case '':
                return NULL;
        }
    }

    private function _statics_url($static_file) {
        $static_host = (parse_url($static_file, PHP_URL_HOST));
        if ($static_host) {
            return $static_file;
        }

        return mdi::$config['statics'].$static_file;
    }

    private function _media_url($media_file) {
        //middle.FIXME
        $path = preg_replace('/^(\/?media?\/)/', '', $media_file);
        return mdi::$config['media'].$path;
    }

    private function _url($url, $default_args=array(), $additional_args=array(), $delete_args=array()) {
        if (!$default_args) {
            $default_args = array();
        }

        $default_args = array_merge(array(), $default_args);

        if (!$additional_args) {
            $additional_args = array();
        }

        if (!empty($delete_args)) {
            foreach ($delete_args as $value) {
                if(isset($default_args[$value])) {
                    unset($default_args[$value]);
                }
            }
        }

        $args = $additional_args + $default_args;

        foreach ($args as $key => $value) {
            reset($args);
            if ($key === key($args)) {
                $url .= '?'.$key.'='.$value;
            } else {
                $url .= '&'.$key.'='.$value;
            }
        }

        return $url;
    }

    public static function debug_print_backtrace() {
        $stack = debug_backtrace();
        $i = 1;
        foreach($stack as $node) {
            print "$i. ".basename($node['file']) .":" .$node['function'] ."(" .$node['line'].")<br>";
            $i++;
        }
    }
}

