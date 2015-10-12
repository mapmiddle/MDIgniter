<?php

// version
$config['version'] = '1.00';

// project
$config['project'] = 'New Project';

// debug
$config['debug'] = TRUE;

// timezone
$config['timezone'] = 'UTC';

// static
$config['statics'] = '/statics/';

// media
$config['media'] = '/media/';

// features
$config['auto_db_sync'] = TRUE;

// user model
define('MDI_USER_MODEL', 'model_user');

// choices
define('MDI_CHOICES_AUTH_GRADE_NONE', 0);
define('MDI_CHOICES_AUTH_GRADE_USER', 10);
define('MDI_CHOICES_AUTH_GRADE_STAFF', 100);
define('MDI_CHOICES_AUTH_GRADE_MASTER', 1000);
define('MDI_CHOICES_AUTH_GRADE',  serialize(array(
    MDI_CHOICES_AUTH_GRADE_NONE => 'none',
    MDI_CHOICES_AUTH_GRADE_USER => 'user',
    MDI_CHOICES_AUTH_GRADE_STAFF => 'staff',
    MDI_CHOICES_AUTH_GRADE_MASTER => 'master',
)));

// admin
$config['admin_controller'] = 'admin';
$config['admin_accessible_grade'] = MDI_CHOICES_AUTH_GRADE_MASTER;

$config['admin_model_table_rows_per_page'] = 10;
$config['admin_model_table_page_per_paginator'] = 9;

$config['admin_using_history'] = FALSE;

$config['admin_default_email'] = 'admin@admin.com';
$config['admin_default_password'] = '1234';
$config['admin_default_grade'] = 1000;

// widgets
$config['fileuploader_thumbnail_image_max_width'] = 160;
$config['fileuploader_thumbnail_image_max_height'] = 160;

// mails
$config['mail'] = array(
    'mailtype' => "html",
    'charset' => "euc-kr",
    'protocol' => "smtp",
    'smtp_host' => "", // smtp host
    'smtp_port' => 465, // smtp port
    'smtp_name' => "admin",
    'smtp_user' => "", // smtp id(email)
    'smtp_pass' => "", // smtp password
    'smtp_timeout' => 30,
);

// mobile push message
$config['google_api_key'] = '';
$config['apns_pem_path'] = ''; // example : 'data/pem/apns-dev.pem'
$config['apns_host'] = 'gateway.sandbox.push.apple.com';

// error code
define('ERROR_CODE_DEFAULT', 100);
define('ERROR_CODE_AUTH', 200);
define('ERROR_CODE_PARAM', 300);