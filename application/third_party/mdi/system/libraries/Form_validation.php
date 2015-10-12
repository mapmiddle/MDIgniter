<?php

require_once BASEPATH.'libraries/Form_validation.php';

class MDI_Form_validation extends CI_Form_validation {
    public function __construct($rules = array()) {
        parent::__construct($rules);
    }

    public function get_all_errors() {
        return $this->_error_array;
    }

    public function add_error_message($field, $message) {
        $this->_error_array[$field] = $message;
    }
}
