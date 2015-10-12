<?php


class MDI_User extends MDI_Model {
    var $table = 'mdi_users';
    var $validation = array(
        'email' => array(
            'label' => 'Email', ///
            'rules' => array('varchar' => 128, 'required', 'trim', 'unique', 'valid_email', 'max_length' => 128)
        ),

        'grade' => array(
            'label' => 'Grade', ///
            'rules' => array('int', 'required', 'choices' => MDI_CHOICES_AUTH_GRADE),
        ),

        'last_activity_datetime' => array(
            'label' => 'Last Activity Datetime', ///
            'rules' => array('datetime')
        ),
    );

    var $has_one = array(
        'credential_native' => array(
            'label' => 'Native Credential',///
            'class' => 'mdi_credential_native',
            'other_field' => 'user'
        ),
    );

    var $has_many = array(
        'mobile_list' => array(
            'label' => 'Mobile List', ///
            'class' => 'mdi_mobile',
            'other_field' => 'user'
        ),

        'history_list' => array(
            'label' => 'History List', ///
            'class' => 'mdi_history',
            'other_field' => 'user',
            'admin' => array('hide'),
        ),
    );

    var $credential_backends = array(
        'mdi_credential_native',
    );

    public function push_message($data) {
        if ($this->mobile_list->get()->exists()) {
            foreach ($this->mobile_list as $mobile) {
                $mobile->push_message($data);
            }
        }

        return true;
    }

    public function send_email($subject, $content, $kwargs=NULL) {
        $ci =& get_instance();
        $ci->mdi->load('features/email');

        $config = $ci->mdi->config('mail');

        $to = $this->email;
        $name = $config['smtp_name'];
        $from = $config['smtp_user'];
        return $ci->mdi->email->send($name, $from, $to, $subject, $content);
    }

    protected function _update_activity() {
        $ci =& get_instance();
        $ci->load->library('user_agent');

        // last_activity_datetime
        $cur_datetime = date('Y-m-d H:i:s', time());
        $this->last_activity_datetime = $cur_datetime;
        $this->save();
    }

    static public function login($kwargs) {
        $result = array();

        // kwargs check
        $ci =& get_instance();
        $ci->load->library('user_agent');

        $push_token = NULL;
        if ($ci->agent->is_mobile_app()) {
            if (!array_key_exists('push_token', $kwargs) || empty($kwargs['push_token'])) {
                $result['error'] = '"push_token" argument is not exists'; ///
                return $result;
            }

            $push_token = $kwargs['push_token'];
        }

        // backend login
        $model = mdi::get_user_model();
        $user = new $model();

        $backend_result = FALSE;
        foreach ($user->credential_backends as $backend) {
            $backend_object = new $backend();
            if (FALSE !== ($backend_result = $backend_object::login($kwargs))) {
                break;
            }
        }

        if (is_string($backend_result)) {
            $result['error'] = $backend_result;
        } else if (is_object($backend_result)) {
            $backend_result->user->get();
            $user->get_by_id($backend_result->user->id);

            if (!$user->exists()) {
                return $result;
            }

            if ($user->disabled) {
                $result['error'] = 'you are the user who was already unregistered or stopped.'; ///
                return $result;
            }

            // save session
            $ci->load->library('session');
            $ci->session->set_userdata(array(
                'user_id' => $user->id,
            ));

            // save mobile device
            if ($ci->agent->is_mobile_app()) {
                $agent = $ci->input->user_agent();
                $mobile = new MDI_Mobile();
                $mobile->where_related('user', 'id', $user->id)->where('agent', $agent)->get();
                if (!$mobile->exists()) {
                    $new_mobile = TRUE;
                }

                $auth_token = mdi_guid();
                $mobile->push_token = $push_token;
                $mobile->auth_token = $auth_token;
                $mobile->agent = $agent;
                $mobile->save();

                if (isset($new_mobile)) {
                    $user->save(array($mobile), 'mobile_list');
                }

                $result['auth_token'] = $auth_token;
            }

            // update activity
            $user->_update_activity();

            $result['user'] = $user;
        }

        return $result;
    }

    static public function logout() {
        $ci =& get_instance();
        $user = self::get_auth_user();

        // remove session
        $ci->load->library('session');
        $ci->session->unset_userdata(array(
            'user_id' => '',
        ));

        if ($user != NULL) {
            // remove mobile device
            $ci->load->library('user_agent');
            if ($ci->agent->is_mobile_app()) {
                $agent = $ci->input->user_agent();
                $mobile = new MDI_Mobile();
                $mobile->where_related('user', 'id', $user->id)->where('agent', $agent)->get();
                if ($mobile->exists()) {
                    $mobile->delete();
                }
            }

            // backend logout
            foreach ($user->credential_backends as $backend) {
                $backend::logout($user);
            }
        }

        return TRUE;
    }

    static public function get_auth_user() {
        static $s_authed_user = NULL;

        if ($s_authed_user) {
            return $s_authed_user;
        }

        // mobile app auth key check
        $ci =& get_instance();
        $ci->load->library('user_agent');
        if ($ci->agent->is_mobile_app()) {
            $auth_token = $ci->input->get_post('auth_token');
            if ($auth_token) {
                $mobile = new MDI_Mobile();
                $mobile->where('auth_token', $auth_token)->get();
                if ($mobile->exists()) {
                    $s_authed_user = $mobile->user->get();

                    // update activity
                    $s_authed_user->_update_activity();

                    return $mobile->user;
                }
            }
        }

        // session check
        if (NULL != ($user = self::get_session_user())) {
            $s_authed_user = $user;

            // update activity
            $s_authed_user->_update_activity();

            return $user;
        }

        return NULL;
    }

    static public function get_session_user() {
        $ci =& get_instance();
        $ci->load->library('session');

        if ($ci->session->userdata('user_id') == FALSE) {
            return NULL;
        }

        $model = mdi::get_user_model();
        $user = new $model();
        $user->where('id', $ci->session->userdata('user_id'))->get();
        if ($user->exists()) {
            return $user;
        }

        return NULL;
    }
}