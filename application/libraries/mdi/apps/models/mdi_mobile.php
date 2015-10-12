<?php



class MDI_Mobile extends MDI_Model {
    var $table = 'mdi_mobiles';
    var $validation = array(
        'agent' => array(
            'label' => 'User Agent', ///
            'rules' => array('varchar' => 64, 'required',)
        ),

        'push_token' => array(
            'label' => 'Push Token', ///
            'rules' => array('varchar' => 255, 'unique', 'required',)
        ),

        'auth_token' => array(
            'label' => 'Auth Token', ///
            'rules' => array('varchar' => 32, 'unique', 'required',)
        ),
    );

    var $has_one = array(
        'user' => array(
            'label' => 'User',
            'class' => MDI_USER_MODEL,
            'other_field' => 'mobile_list'
        ),
    );

    public function is_android() {
        $ci =& get_instance();
        $ci->load->library('user_agent');
        return $this->agent == $ci->agent->get_mobile_app_agent('android');
    }

    public function is_ios() {
        $ci =& get_instance();
        $ci->load->library('user_agent');
        return $this->agent == $ci->agent->get_mobile_app_agent('ios');
    }

    public function push_message($data) {
        $ci =& get_instance();
        $ci->mdi->load('features/pushmessage');

        if (MDI_Environment::get_value('ENABLE_PUSH_MESSAGE', 0) != '1') {
            return FALSE;
        }

        if ($this->is_android()) {
            return $ci->mdi->pushmessage->send_to_android(MDI_Environment::get_value('GOOGLE_API_KEY'), $this->push_token, $data);
        } else if($this->is_ios()) {
            return $ci->mdi->pushmessage->send_to_ios(
                MDI_Environment::get_value('APNS_HOST'),
                MDI_Environment::get_value('APNS_PEM_PATH'), $this->push_token, $data);
        } else {
            // middle.log_error
            return FALSE;
        }
    }
};