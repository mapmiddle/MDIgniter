<?php


require_once(APPPATH.'libraries/mdi/apps/class/mdi_admin_controller.php');

class Admin extends MDI_Admin_Controller {
    var $dashboard = array(
        'group 1' => array(
            'Model_User',
        ),

        'group 2' => array(
            'mdi_environment',
            'mdi_credential_native',
            'mdi_log',
            'mdi_history',
            'mdi_mobile',
        ),
    );

    function __construct() {
        parent::__construct();
    }

    protected function _create_default_admin() {
        $user = new Model_User();
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
        $user->name = 'Admin';
        $user->phone = '0000-0000';
        $user->save($credential, 'credential_native');
    }
}