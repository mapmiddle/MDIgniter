<?php


class MDI_Credential_Native extends MDI_Model {
    var $table = 'mdi_credential_natives';
    var $validation = array(
        'email' => array(
            'label' => 'Email', ///
            'rules' => array('varchar' => 128, 'required', 'trim', 'unique', 'valid_email', 'max_length' => 128)
        ),

        'password' => array(
            'label' => 'Password', ///
            'rules' => array('varchar' => 128, 'required', 'encrypt', 'min_length' => 4, 'max_length' => 128),
        ),
    );

    var $has_one = array(
        'user' => array(
            'label' => 'User', ///
            'class' => MDI_USER_MODEL,
            'other_field' => 'credential_native',
            'auto_populate' => TRUE,
        ),
    );

    static public function login($kwargs) {
        if (!array_key_exists('email', $kwargs) || !array_key_exists('password', $kwargs)) {
            return FALSE;
        }

        $credential = new self();
        $credential->where('email', $kwargs['email'])->get();
        if (!$credential->exists()) {
            return 'id or password are invalid';///
        }

        $enc_pass = self::encrypt_password($kwargs['password']);
        if (strcasecmp($enc_pass, $credential->password) != 0) {
            return 'id or password are invalid';///
        }

        // autologin
        if (array_key_exists('autologin', $kwargs) && $kwargs['autologin']) {
            $ci =& get_instance();
            $ci->load->helper('cookie');

            set_cookie(array(
                'name' 		=> 'autologin_native',
                'value'		=> serialize(array('email' => $credential->email, 'password' => $enc_pass)),
                'expire'	=> 86500,
            ));
        }

        return $credential;
    }

    static public function logout($user) {
        // remove autologin
        $ci =& get_instance();
        $ci->load->helper('cookie');
        delete_cookie('autologin_native');
    }

    public function password_value_set($value) {
        if ($this->_trim_value($this->password) != $this->_trim_value($value)) {
            $this->_need_encrpyt = TRUE;
        }

        $this->password = $value;
    }

    static public function encrypt_password($password) {
        return sha1(md5($password));
    }

    // Validation prepping function to encrypt passwords
    // If you look at the $validation array, you will see the password field will use this function
    function _encrypt($field) {
        // Don't encrypt an empty string
        if (!empty($this->{$field})) {
            // already encrypt check
            if (isset($this->_need_encrpyt) && $this->_need_encrpyt) {
                $this->{$field} = self::encrypt_password($this->{$field});
            }
        }
    }
}