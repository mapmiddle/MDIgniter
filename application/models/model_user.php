<?php

class Model_User extends MDI_User {
    var $label = 'User';
    var $table = "users";
    var $abstract = FALSE;

    var $validation = array(
        'name' => array(
            'label' => 'Name',
            'rules' => array('varchar' => 32, 'required', 'trim', 'index'),
        ),

        'phone' => array(
            'label' => 'Phone',
            'rules' => array('varchar' => 32, 'required', 'trim', 'unique'),
        ),

        'introduce' => array(
            'label' => 'Introduce',
            'rules' => array('text'),
        ),

        'enable' => array(
            'label' => 'Enable',
            'rules' => array('boolean', 'default' => true),
        ),
    );
}
