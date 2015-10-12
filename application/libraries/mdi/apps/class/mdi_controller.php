<?php
/**
 * mdi controller annotations
 *
 *  - only_desktop [none|boolean]
 *  - only_mobile [none|boolean]
 *  - only_mobile_app [none|boolean]
 *  - only_mobile_web [none|boolean]
 *  - only_get [none|boolean]
 *  - only_post [none|boolean]
 *  - only_ajax [none|boolean]
 *  - only_debug [none|boolen]
 *  - agent_desktop [none|boolean]
 *  - agent_mobile [none|boolean]
 *  - agent_mobile_app [none|boolean]
 *  - agent_mobile_web [none|boolean]
 *  - agent_robot [none|boolean]
 *  - request_get [none|boolean]
 *  - request_post [none|boolean]
 *  - request_ajax [none|boolean]
 *  - require_auth [none|boolean]
 *  - require_admin [none|boolean]
 *  - require_grade [grade:number]
 *  - param_get [type:string] [name:string]
 *  - param_post [type:string] [name:string]
 *  - utf_8 [none|boolean]
 *  - no_cache [none|boolean]
 *  - header [header:stirng] [none|replace:boolean]
 *  - cache_control [content:string] [none|replace:boolean]
 *  - expires [content:string] [none|replace:boolean]
 *  - content_type [content:string] [none|replace:boolean]
 *  - pragma [content:string] [none|replace:boolean]
 *
 */
class MDI_Controller extends CI_Controller {
    function __construct() {
        try{
            parent::__construct();

            // load libraries
            $this->load->library('user_agent');
            $this->mdi->load('features/annotation');

            // user setting
            $user_model = mdi::get_user_model();
            $this->user = call_user_func($user_model .'::get_auth_user');
        } catch (Exception $e) {
            MDI_Log::write($e->getMessage());
            mdi::error(json_encode(array('error' => $e->getMessage(), 'code' => ERROR_CODE_DEFAULT)));
        }
    }

    function _remap($method, $args=array()) {
        try{
            if (method_exists($this, $method)) {
                if ($this->_pre_execute($method, $args)) {
                    call_user_func_array(array(&$this, $method), $args);
                }
            } else {
                show_404(get_class($this).'/'.$method);
            }
        } catch (Exception $e) {
            MDI_Log::write($e->getMessage());
            mdi::error(json_encode(array('error' => $e->getMessage(), 'code' => ERROR_CODE_DEFAULT)));
        }
    }

    protected function _pre_execute($method, $args) {
        // controller name
        $this->controller_name = strtolower(get_class($this));

        // method name
        $this->method_name = $method;

        // method agrs
        $this->method_args = $args;

        // annotation setting
        $params_class = array();
        foreach (array(get_class($this) => get_class($this)) + class_parents($this) as $class) {
            if ($class == 'CI_Controller') {
                break;
            }

            $params_class = $params_class + $this->mdi->annotation->parse($class);
        }

        $params_method = $this->mdi->annotation->parse(get_class($this), $method);
        $params = $params_method + $params_class;

        foreach (array_reverse($params) as $key => $value) {
            if (method_exists($this, '_annotation_'.$key)) {
                try {
                    if (is_array($value)) {
                        $values = $value;
                    } else {
                        $values = array($value);
                    }

                    foreach($values as $v) {
                        call_user_func_array(array(&$this, '_annotation_'.$key), explode(' ', $v));
                    }

                } catch(MDI_ControllerAnnotation_Exception $e) {
                    return FALSE;
                }
            }
        }

        return TRUE;
    }

    protected function _annotation_error($annotation, $message, $status=500) {
        if (method_exists($this, '_annotation_error_'.$annotation)) {
            call_user_func_array(array(&$this, '_annotation_error_'.$annotation), array($annotation, $message, $status));
        } else {
            mdi::error('request error : '.$message.'('.$annotation.")", $status);
        }

        throw new MDI_ControllerAnnotation_Exception();
    }

    protected function _annotation_only_desktop($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if ($enabled) {
            if (!$this->agent->is_desktop()) {
                $this->_annotation_error('only_desktop', 'forbidden');
            }
        }
    }

    protected function _annotation_only_mobile($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if ($enabled) {
            if (!$this->agent->is_mobile()) {
                $this->_annotation_error('only_mobile', 'forbidden');
            }
        }
    }

    protected function _annotation_only_mobile_app($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if ($enabled) {
            if (!$this->agent->is_mobile_app()) {
                $this->_annotation_error('only_mobile_app', 'forbidden');
            }
        }
    }

    protected function _annotation_only_mobile_web($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if ($enabled) {
            if (!$this->agent->is_mobile_web()) {
                $this->_annotation_error('only_mobile_web', 'forbidden');
            }
        }
    }

    protected function _annotation_only_get($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if ($enabled) {
            if ($this->input->server('REQUEST_METHOD') != 'GET') {
                $this->_annotation_error('only_get', 'forbidden');
            }
        }
    }

    protected function _annotation_only_post($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if ($enabled) {
            if ($this->input->server('REQUEST_METHOD') != 'POST') {
                $this->_annotation_error('only_post', 'forbidden');
            }
        }
    }

    protected function _annotation_only_ajax($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if ($enabled) {
            if (!$this->input->is_ajax_request()) {
                $this->_annotation_error('only_ajax', 'forbidden');
            }
        }
    }

    protected function _annotation_only_debug($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if ($enabled) {
            if (!$this->mdi->is_debug()) {
                $this->_annotation_error('only_debug', 'forbidden');
            }
        }
    }

    protected function _annotation_agent_desktop($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if (!$enabled) {
            if ($this->input->is_desktop()) {
                $this->_annotation_error('agent_desktop', 'forbidden');
            }
        }
    }

    protected function _annotation_agent_mobile($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if (!$enabled) {
            if ($this->input->is_mobile()) {
                $this->_annotation_error('agent_mobile', 'forbidden');
            }
        }
    }

    protected function _annotation_agent_mobile_app($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if (!$enabled) {
            if ($this->agent->is_mobile_app()) {
                $this->_annotation_error('agent_mobile_app', 'forbidden');
            }
        }
    }

    protected function _annotation_agent_mobile_web($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if (!$enabled) {
            if ($this->agent->is_mobile_web()) {
                $this->_annotation_error('agent_mobile_web', 'forbidden');
            }
        }
    }

    protected function _annotation_agent_robot($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if (!$enabled) {
            if ($this->agent->is_robot()) {
                $this->_annotation_error('agent_robot', 'forbidden');
            }
        }
    }

    protected function _annotation_request_get($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if (!$enabled) {
            if ($this->input->server('REQUEST_METHOD') == 'GET') {
                $this->_annotation_error('request_get', 'forbidden');
            }
        }
    }

    protected function _annotation_request_post($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if (!$enabled) {
            if ($this->input->server('REQUEST_METHOD') == 'POST') {
                $this->_annotation_error('request_post', 'forbidden');
            }
        }
    }

    protected function _annotation_request_ajax($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if (!$enabled) {
            if ($this->input->is_ajax_request()) {
                $this->_annotation_error('request_ajax', 'forbidden');
            }
        }
    }

    protected function _annotation_require_auth($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if ($enabled) {
            if ($this->user == NULL) {
                $this->_annotation_error('require_auth', 'forbidden');
            }
        }
    }

    protected function _annotation_require_admin($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if ($enabled) {
            $this->_annotation_require_auth($enabled);

            if ($this->user->grade < mdi::config('admin_accessible_grade')) {
                $this->_annotation_error('require_admin', 'forbidden');
            }
        }
    }

    protected function _annotation_require_grade($grade) {
        $this->_force_type_casting('int', $grade);
        $this->_annotation_require_auth(TRUE);

        if ($this->user->grade < $grade) {
            $this->_annotation_error('require_grade', 'forbidden');
        }
    }

    protected function _annotation_header($header, $replace=TRUE) {
        $this->_force_type_casting('boolean', $replace);
        $this->output->set_header($header, $replace);
    }

    protected function _annotation_cache_control($content, $replace=TRUE) {
        $this->_force_type_casting('boolean', $replace);
        $this->output->set_header('Cache-Control: '.$content, $replace);
    }

    protected function _annotation_expires($content, $replace=TRUE) {
        $this->_force_type_casting('boolean', $replace);
        $this->output->set_header('Expires: '.$content, $replace);
    }

    protected function _annotation_pragma($content, $replace=TRUE) {
        $this->_force_type_casting('boolean', $replace);
        $this->output->set_header('Pragma: '.$content, $replace);
    }

    protected function _annotation_content_type($content, $replace=TRUE) {
        $this->_force_type_casting('boolean', $replace);
        $this->output->set_header('Content-Type: '.$content, $replace);
    }

    protected function _annotation_no_cache($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if ($enabled) {
            $this->output->set_header("Expires: Mon, 26 Jul 1990 05:00:00 GMT");
            $this->output->set_header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            $this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
            $this->output->set_header("Cache-Control: post-check=0, pre-check=0", false);
            $this->output->set_header("Pragma: no-cache");
        }
    }

    protected function _annotation_utf_8($enabled) {
        $this->_force_type_casting('boolean', $enabled);
        if ($enabled) {
            $this->output->set_header('Content-Type: text/html; charset=UTF-8');
        }
    }

    protected function _annotation_param_get_post($type, $name) {
        if (!isset($type) || !isset($name)) {
            $this->_annotation_error('param_get_post', 'annotation syntax error');
        }

        $request_method = strtoupper($this->input->server('REQUEST_METHOD'));
        if ($request_method == 'GET') {
            $this->_param_check('param_get', 'GET', $type, $name);
        } else if ($request_method == 'POST') {
            $this->_param_check('param_post', 'POST', $type, $name);
        }
    }

    protected function _annotation_param_get($type, $name) {
        if (!isset($type) || !isset($name)) {
            $this->_annotation_error('param_get', 'annotation syntax error');
        }

        $this->_param_check('param_get', 'GET', $type, $name);
    }

    protected function _annotation_param_post($type, $name) {
        if (!isset($type) || !isset($name)) {
            $this->_annotation_error('param_post', 'annotation syntax error');
        }

        $this->_param_check('param_post', 'POST', $type, $name);
    }

    protected function _param_check($annotation, $request_method, $type, $name) {
        $request_method = strtoupper($request_method);
        if (strtoupper($this->input->server('REQUEST_METHOD')) != strtoupper($request_method)) {
            return;
        }

        $kwargs = array();
        switch ($request_method) {
            case 'GET':
                $kwargs = $this->input->get();
                break;
            case 'POST':
                $kwargs = $this->input->post();
                break;
            default:
                $this->_annotation_error($annotation, 'unknown request method'); ///
        }

        if (is_bool($kwargs)) {
            $kwargs = array();
        }

        if (!array_key_exists($name, $kwargs)) {
            $this->_annotation_error($annotation, "'$name' parameter does not exist"); ///
        }

        // middle.fixme
        // please type check
    }

    protected function &_force_type_casting($type, &$value) {
        $value = force_type_casting($type, $value);
        return $value;
    }
}

class MDI_ControllerAnnotation_Exception extends Exception {}
