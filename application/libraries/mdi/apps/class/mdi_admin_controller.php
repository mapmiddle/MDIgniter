<?php


require_once(APPPATH.'libraries/mdi/apps/class/mdi_controller.php');

/**
 *
 *
 * @require_admin
 * @utf_8
 */
class MDI_Admin_Controller extends MDI_Controller {
    var $models = array();
    var $dashboard = array();

    function __construct() {
        parent::__construct();
        $this->load->library('form_validation');
    }

    function initialize() {
        // parse model
        $this->_parse_using_model($this->dashboard);

        // create user
        $this->_create_default_admin();
    }

    function _remap($method, $args=array()) {
        $this->initialize();

        if(empty($method) || $method == 'index') {
            // dashboard
            if ($this->_pre_execute('dashboard', $args)) {
                $this->dashboard();
            }
        } else if($method == 'model') {
            // model
            if ($this->_pre_execute('model', $args)) {
                $this->model(
                    array_get($args[0], NULL),
                    array_get($args[1], NULL));
            }
        } else if($method == 'popup') {
            // popup
            if ($this->_pre_execute('popup', $args)) {
                $this->popup(
                    array_get($args[0], NULL));
            }
        } else if($method == 'ajax') {
            // ajax
            if ($this->_pre_execute('ajax', $args)) {
                $this->ajax($args[0]);
            }
        } else if($method == 'logout') {
            // logout
            if ($this->_pre_execute('logout', $args)) {
                $this->logout();
            }
        }
    }

    function login() {
        $this->mdi->statics_lazy('css', 'mdi/admin/admin-login.css');
        $this->navs = array(
            mdi::config('project') => admin_url(),
        );

        $this->form_validation->set_rules('email', 'email', 'trim|required|xss_clean'); ///
        $this->form_validation->set_rules('password', 'password', 'trim|required|xss_clean'); ///

        if ($this->form_validation->run()) {
            $model = mdi::get_user_model();
            $ret = call_user_func($model.'::login', array(
                'email' => $this->form_validation->set_value('email'),
                'password' => $this->form_validation->set_value('password')));

            if (array_key_exists('user', $ret)) {
                redirect(current_url());
                return;
            } else if (array_key_exists('error', $ret)) {
                $this->form_validation->add_error_message('email', $ret['error']);///
            }
        }

        $this->load->view('mdi_admin_top_v');
        $this->load->view('mdi_admin_login_v');
        $this->load->view('mdi_admin_bottom_v');
    }

    public function logout() {
        $redirect_url = $this->input->get_post('redirect_url', NULL);
        if (!$redirect_url) {
            $redirect_url = admin_url();
        }

        $model = mdi::get_user_model();
        $user = call_user_func($model.'::get_auth_user');
        if (!$user) {
            redirect($redirect_url);
        }

        call_user_func($model.'::logout');
        redirect($redirect_url);
    }

    public function permission_denied() {
        echo 'permission denied'; ///
    }

    public function dashboard() {
        $this->navs = array(
            mdi::config('project') => admin_url(),
        );

        $this->render_dashboard = array();
        $this->_parse_dashboard($this->dashboard, $this->parse_dashboard);

        $this->load->view('mdi_admin_top_v');
        $this->load->view('mdi_admin_dashboard_v');
        $this->load->view('mdi_admin_bottom_v');
    }

    public function model($model, $action) {
        if ($model == NULL) {
            $model = array_get($this->input->get('model'), NULL);
            $id = array_get($this->input->get('id'), NULL);

            if ($model && $id) {
                redirect(admin_url('model/'.$model.'/'.$id));
            } else {
                redirect(admin_url());
            }
        }

        if (!array_ikey_exists($model, $this->models)) {
            mdi::error("MDI_Admin_Controller::model Error - model did not defined in Dashboard(".$model.")");///
        }

        if ($action == NULL) {
            $this->_model_table($model);
        } else {
            $action = strtoupper($action);

            if ($action == 'DELETE') {
                $this->_model_delete($model);
            } else {
                $this->_model_edit($model, $action);
            }
        }
    }

    public function popup($type) {
        $model = $this->input->get_post('model');

        if($type == 'search_model') {
            $this->_parse_popup_search_model($model);
            $this->load->view('mdi_admin_popup_top_v');
            $this->load->view('mdi_admin_popup_search_model_v');
            $this->load->view('mdi_admin_popup_bottom_v');
        }
    }

    public function ajax($type) {
        if($type == 'command') {
            $this->_ajax_command();
        } else if($type == 'search_model') {
            $this->_ajax_search_model();
        }
    }

    function _annotation_error_require_auth() {
        $this->initialize();
        $this->login();
    }

    function _annotation_error_require_admin() {
        $this->initialize();
        $this->permission_denied();
    }

    public function _permission_check() {
        if (!$this->user) {
            return FALSE;
        }

        if ($this->user->grade >= mdi::config('admin_accessible_grade')) {
            return TRUE;
        }

        return FALSE;
    }

    protected function _model_table($model) {
        $this->navs = array(
            mdi::config('project') => admin_url(),
            'Model('.$model.')' => admin_url('model/'.$model),
        );

        $this->load->model($model);
        $this->_parse_model_table($model);

        $this->load->view('mdi_admin_top_v');
        $this->load->view('mdi_admin_model_table_v');
        $this->load->view('mdi_admin_bottom_v');
    }

    protected function _model_edit($model, $action) {
        $this->mdi->statics_lazy('css', 'mdi/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css');
        $this->navs = array(
            mdi::config('project') => admin_url(),
            'Model('.$model.')' => admin_url('model/'.$model),
        );

        if (is_numeric($action)) {
            $this->navs["Edit($action)"] = current_url(); ///
        } else if ($action == 'NEW') {
            $this->navs["New"] = current_url(); ///
        }

        if (is_request_method('GET')) {
            $this->_parse_model_edit($model, $action);

            $this->load->view('mdi_admin_top_v');
            $this->load->view('mdi_admin_model_edit_v');
            $this->load->view('mdi_admin_bottom_v');
        } else if (is_request_method('POST')) {
            if ($this->_save_model_edit($model, $action)) {
                $data = array(
                    'type' => 'success',
                    'title' => 'Success',
                    'message' => 'Save completed',
                    'url' => admin_url('model/'.$model)
                );

                $this->load->view('mdi_admin_top_v');
                $this->load->view('templates/mdi_admin_message_t', $data);
                $this->load->view('mdi_admin_bottom_v');
            } else {
                $this->_parse_model_edit($model, $action);
                $this->load->view('mdi_admin_top_v');
                $this->load->view('mdi_admin_model_edit_v');
                $this->load->view('mdi_admin_bottom_v');
            }
        }
    }

    protected function _model_delete($model) {
        $this->navs = array(
            mdi::config('project') => admin_url(),
            'Model('.$model.')' => admin_url('model/'.$model),
        );
        $this->navs["Delete"] = current_url();

        $delete_ids = $this->input->get_post('deleteIds');
        if (empty($delete_ids)) {
            redirect(admin_url('model/'.$model));
        }

        $confirmed = $this->input->get_post('confirmed');
        if ($confirmed) {
            $object = new $model();
            $object->where_in('id', $delete_ids)->get();

            if (!$object->exists()) {
                mdi::error("MDI_Admin_Controller::model_delete Error - object did not found(ids:".implode(',', $delete_ids).")");///
            }

            $object->delete_all();

            $data = array(
                'type' => 'success',
                'title' => 'Sucess',
                'message' => 'Delete completed',
                'url' => admin_url('model/'.$model),
            );

            $this->load->view('mdi_admin_top_v');
            $this->load->view('templates/mdi_admin_message_t', $data);
            $this->load->view('mdi_admin_bottom_v');
        } else {
            $data = array(
                'type' => 'warning',
                'title' => 'Warning',
                'message' => 'these objects will be deleted(ids:'.implode(',', $delete_ids).')', ///
                'url' => admin_url('model/'.$model.'/delete'),
                'method' => 'post',
                'data' => array(
                    'deleteIds' => $delete_ids,
                    'confirmed' => TRUE
                ),
            );

            $this->load->view('mdi_admin_top_v');
            $this->load->view('templates/mdi_admin_message_t', $data);
            $this->load->view('mdi_admin_bottom_v');
        }
    }

    public function _ajax_command(){
        $json = array(
            'success' => TRUE,
            'data' => NULL,
        );

        $commandLine = $this->input->get_post('commandLine');

        if(!$commandLine) {
            $json['data'] = 'Enter the command';///
            echo json_encode($json);
            return;
        }

        $this->_parse_command($commandLine);

        if (isset($this->parse_command)) {
            $command = $this->parse_command['_COMMAND'];
            $args = $this->parse_command['_ARGS'];

            if ($this->mdi->load('commands/'.$command, FALSE)) {
                $json['data'] = call_user_func_array($command, array_merge(array($this), $args));
            } else {
                $json['data'] = "Unknown command($command)";///
            }
        } else {
            $json['data'] = 'Can not interpreted';///
        }

        echo json_encode($json);
    }

    public function _ajax_search_model() {
        $json = array(
            'success' => TRUE,
            'data' => NULL,
        );

        $model = $this->input->get_post('model');
        $fields = $this->input->get_post('fields');
        $limit = 30;

        if (empty($model) || empty($fields)) {
            $json['success'] = FALSE;
            $json['data'] = 'The wrong approach';///
            echo json_encode($json);
            return;
        }

        $object = new $model();
        $like = array();

        foreach($fields as $field => $value) {
            $like[$field] = $value;
        }

        $object->like($like)->limit($limit);
        $object->get();

        $count = count($object->all);
        $json['data'] = array(
            'objects' => array(),
            'count' => $count,
            'continuous' => ($count == $limit) ? TRUE : FALSE,
        );

        foreach ($object->all as $find_obj) {
            $json['data']['objects'][] = array(
                'id' => $find_obj->id,
                'label' => (string)$find_obj,
            );
        }

        echo json_encode($json);
    }

    protected function _parse_command($commandLine) {
        $pieces = array_filter(explode(' ', $commandLine));
        $this->parse_command = array(
            '_COMMAND' => $pieces[0],
            '_ARGS' => array_slice($pieces, 1),
        );
    }

    protected function _parse_using_model($dashboard) {
        foreach ($dashboard as $key => $value) {
            if (is_numeric($key)) {
                $this->models[$value] = NULL;
            } else {
                if (is_array($value)) {
                    $this->_parse_using_model($value);
                } else if (is_string($value)) {
                    $this->models[$value] = NULL;
                }
            }
        }
    }

    protected function _parse_dashboard($dashboard, &$parse_dashboard) {
        foreach ($dashboard as $key => $value) {
            $model = NULL;
            $group = NULL;
            $group_label = NULL;

            if (is_numeric($key)) {
                if (array_ikey_exists($value, $this->models)) {
                    $model = $value;
                }
            } else {
                if (is_array($value)) {
                    $group = $value;
                    $group_label = $key;
                } else if (is_string($value)) {
                    if (array_ikey_exists($value, $this->models)) {
                        $model = $value;
                    }
                }
            }

            if ($model) {
                $this->load->model($model);
                $object = $this->{$model};
                $label = $object->label();

                $parse_dashboard[] = array(
                    '_TYPE' => 'MODEL',
                    '_OBJECT' => $object,
                    '_MODEL' => $model,
                    '_LABEL' => $label,
                    '_TABLE' => $object->table,
                    '_COUNT' => $object->count(),
                    '_LINK' => admin_url("model/".$model),
                );

            } else if($group) {
                $parse_dashboard[] = array(
                    '_TYPE' => 'GROUP',
                    '_LABEL' => $group_label,
                    '_CHILDREN' => array(),
                );

                end($parse_dashboard);
                $this->_parse_dashboard($group, $parse_dashboard[key($parse_dashboard)]['_CHILDREN']);
            }

        } // foreach end
    }

    protected function _parse_model_table($model) {
        $object = new $model();
        $label = $object->label();
        $table = $object->table;

        $this->parse_model_table = array(
            '_MODEL' => $model,
            '_LABEL' => $label,
            '_TABLE' => $table,
        );

        $rows_per_page = $this->input->get_default('rows_per_page', mdi::config('admin_model_table_rows_per_page'));
        $page_per_paginator = $this->input->get_default('page_per_paginator', mdi::config('admin_model_table_page_per_paginator'));
        $page = $this->input->get_default('page', 1);

        $rows = new $model();
        $rows->default_order_by = array('created_time' => 'desc');
        $rows->mdi_get_paged($page, $rows_per_page, $page_per_paginator);
        $this->parse_model_table['_OBJECT'] = $object;
        $this->parse_model_table['_ROWS'] = $rows;
    }

    protected function _parse_model_edit($model, $action) {
        $object = new $model();
        $label = $object->label();
        $table = $object->table;
        $error = isset($this->error) ? $this->error : array();

        $this->parse_model = array(
            '_MODEL' => $model,
            '_LABEL' => $label,
            '_TABLE' => $table,
            '_ERROR' => $error,
        );

        $data = NULL;

        if (is_numeric($action)) {
            $id = $action;
            $object->get_by_id($id);

            if (!$object->exists()) {
                mdi::error("MDI_Admin_Controller::_parse_model_edit Error - Object did not found(id:$id)");///
            }

            $data = $object;
            if (is_request_method('POST')) {
                $data->set_fields($this->input->post());
            }

            $this->parse_model['_EXISTS_OBJECT'] = $data;
        } else if ($action == 'NEW') {
            if (is_request_method('POST')) {
                $data = $this->input->post();
            }
        } else {
            mdi::error("MDI_Admin_Controller::_parse_model_edit Error - Unknown action($action)");///
        }

        $this->parse_model['_FIELDS'] = array();
        $this->parse_model['_FIELDS']['_DEFAULT_FIELDS'] = array('_LABEL' => 'Default field', '_CHILDREN' => array());///
        $this->parse_model['_FIELDS']['_OTO_FIELDS'] = array('_LABEL' => 'One to One Relation field', '_CHILDREN' => array()); ///
        $this->parse_model['_FIELDS']['_OTM_FIELDS'] = array('_LABEL' => 'One to Many Relation field', '_CHILDREN' => array()); ///
        $this->parse_model['_FIELDS']['_MTO_FIELDS'] = array('_LABEL' => 'Many to One Relation field', '_CHILDREN' => array()); ///
        $this->parse_model['_FIELDS']['_MTM_FIELDS'] = array('_LABEL' => 'Many to Many Relation field', '_CHILDREN' => array()); ///

        foreach(array(
                    $object->default_fields,
                    $object->related_fields
                ) as $fields)
        {
            foreach ($fields as $field => $attributes) {
                $widget = $object->{$field.'_as_admin_widget'}($this->user, $data);
                if ($widget == NULL) {
                    continue;
                }

                $parse_field = array(
                    '_FIELD' => $field,
                    '_LABEL' => $object->{$field.'_label_get'}(),
                    '_WIDGET' => $widget,
                );

                // check required
                if ($object->{$field.'_has_rule'}('required')) {
                    $parse_field['_REQUIRED'] = TRUE;
                }

                // check related
                if (array_key_exists($field, $object->related_fields)) {
                    $parse_field['_TYPE'] = $object->related_fields[$field]['type'];
                    $parse_field['_CLASS'] = $object->related_fields[$field]['class'];
                }

                // check error
                if(array_key_exists($field, $error)) {
                    $parse_field['_ERROR'] = $error[$field];
                }

                // parser
                if (array_key_exists($field, $object->related_fields)) {
                    $related_type = $object->get_related_type($field);
                    if ($related_type == 'oto') {
                        $parser =& $this->parse_model['_FIELDS']['_OTO_FIELDS'];
                    } else if ($related_type == 'otm') {
                        $parser =& $this->parse_model['_FIELDS']['_OTM_FIELDS'];
                    } else if ($related_type == 'mto') {
                        $parser =& $this->parse_model['_FIELDS']['_MTO_FIELDS'];
                    } else if ($related_type == 'mtm') {
                        $parser =& $this->parse_model['_FIELDS']['_MTM_FIELDS'];
                    } else {
                        $parser =& $this->parse_model['_FIELDS']['_DEFAULT_FIELDS'];
                    }

                } else {
                    $parser =& $this->parse_model['_FIELDS']['_DEFAULT_FIELDS'];
                }

                $parser['_CHILDREN'][] = $parse_field;
            }
        }
    }

    protected function _save_model_edit($model, $action) {
        $object = new $model();
        $class = get_class($object);
        $save_object = NULL;

        if (is_numeric($action)) {
            $id = $action;
            $object->get_by_id($id);

            if (!$object->exists()) {
                mdi::error("MDI_Admin_Controller::_save_model_edit Error - Object did not found(id:$id)");///
            }

            $save_object = $object;
        } else if ($action == 'NEW') {
            $save_object = new $class();
        } else {
            mdi::error("MDI_Admin_Controller::_save_model_edit Error - Unknown action($action)");///
        }

        if (is_request_method('POST')) {
            $save_object->set_fields($this->input->post());
        }

        if ($save_object->save()) {
            return TRUE;
        }

        $save_object->skip_validation(FALSE);
        $this->error = $save_object->error->all;
        return FALSE;
    }

    protected function _parse_popup_search_model($model) {
        $object = new $model();
        $label = $object->label();

        $this->parse_popup_search_model = array(
            '_OBJECT' => $object,
            '_MODEL' => $model,
            '_LABEL' => $label,
        );

    }

    protected function _create_default_admin() {
        $model = mdi::get_user_model();
        $user = new $model();
        $user->where('email', mdi::config('admin_default_email'))->get();

        if ($user->exists()) {
            return;
        }

        $credential = new MDI_Credential_Native();
        $credential->email = mdi::config('admin_default_email');
        $credential->password = mdi::config('admin_default_password');
        $credential->_need_encrpyt = TRUE;
        $credential->save();

        $user->email = mdi::config('admin_default_email');
        $user->grade = mdi::config('admin_default_grade');
        $user->save($credential, 'credential_native');
    }
}