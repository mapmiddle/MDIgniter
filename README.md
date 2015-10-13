# MDIgniter
Third party library of CodeIgniter

# Features
- Additional features of [CodeIgniter 2.X](http://www.codeigniter.com/)
- Additional features of [Datamapper ORM](http://datamapper.wanwizard.eu/) 
- Administrator panel support
- Auto database schema build
- Controller annotation with a Django style
- Mobile response(push message...and more)

# Requirements
- PHP 5.3+
- CodeIgniter 2.X [download](http://www.codeigniter.com/download)

### PHP Extension Requirements
- curl.dll
- gd2.dll 

# Installation
1 . Installation **CodeIgniter 2.X** [download](http://www.codeigniter.com/download)

2 . Copy the **MDI files** into your **CodeIgniter root**.

3 . **Create your database** and modify **application/config/database.php** in CodeIgniter

4 . **application/config/autoload.php** change the file as follows:
```
$autoload['libraries'] = array('database', 'session', 'mdi');
$autoload['helper'] = array('url', 'file');
```
5 . **application/config/routes.php** change the file as follows:
```
$route['admin'] = "admin";
$route['admin/(:any)'] = "admin/$1";
```
6 . **application/config/config.php** change the file :
```
$config['encryption_key'] = "YOUR KEY";
```
7 . open your **CodeIgniter /index.php** file and add the MDI bootstrap, directly **before the Codeigniter bootstrap**.

```
...

/* --------------------------------------------------------------------
 * LOAD THE MDI BOOTSTRAP FILE
 * --------------------------------------------------------------------
 */
require_once APPPATH . 'third_party/mdi/bootstrap.php';

/*
 * --------------------------------------------------------------------
 * LOAD THE BOOTSTRAP FILE
 * --------------------------------------------------------------------
 *
 * And away we go...
 *
 */
require_once BASEPATH.'core/CodeIgniter.php';


/* End of file index.php */
/* Location: ./index.php */
```
8 . Now, when you browse to **/index.php/admin** will see the following screen
<img src="https://cloud.githubusercontent.com/assets/7834058/10424112/4cf79544-7107-11e5-826a-b40961e6ce99.png" height="500">
- By default, you can login using email(admin@admin.com) and password(1234)

# Screenshots
<img src="https://cloud.githubusercontent.com/assets/7834058/10424113/4d205a24-7107-11e5-9153-e89d615bd260.png" height="250">
<img src="https://cloud.githubusercontent.com/assets/7834058/10424114/4d3802dc-7107-11e5-9d48-2cc35126bbfa.png" height="250">
<img src="https://cloud.githubusercontent.com/assets/7834058/10424115/4d3b3af6-7107-11e5-8d51-818bd1f00af7.png" height="250">

# Reference
- [CodeIgniter 2.X](http://www.codeigniter.com/)
- [Datamapper ORM](http://datamapper.wanwizard.eu/)
- [JQuery File Upload](https://blueimp.github.io/jQuery-File-Upload/)
- [Bootstrap 3.3.1](http://getbootstrap.com/)

# Example
- Full Example(includes CodeIgniter 2.2.4) [download](https://www.dropbox.com/s/frbgd74x1pdiaw9/mdigniter_example.zip)

# Documentation
## Usage of ORM
MDI uses Datamapper Libraries.
Its default usage as follows :

***User Model***
```
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

    var $has_one = array(
        'book' => array(
            'label' => 'Book',
            'class' => 'model_book',
            'other_field' => 'author'
        ),
    );

    var $has_many = array(
        'rental_list' => array(
            'label' => 'Rental list',
            'class' => 'model_book',
            'other_field' => 'borrowed_user_list',
        ),
    );

}
```

***Book Model***
```
class Model_Book extends MDI_Model {
    var $label = 'Book';
    var $table = "books";
    var $abstract = FALSE;

    var $validation = array(
        'name' => array(
            'label' => 'Name',
            'rules' => array('varchar' => 32, 'required', 'min_length' => 3, 'max_length' => 20),
        ),

        'book_code' => array(
            'label' => 'Book code',
            'rules' => array('varchar' => 32, 'required', 'build_code'),
        ),

        'read_only_value' => array(
            'label' => 'Read only value',
            'rules' => array('int'),
            'admin' => array('read_only'),
        ),

        'hide_value' => array(
            'label' => 'Hide value',
            'rules' => array('int'),
            'admin' => array('hide'),
        ),
    );

    var $has_one = array(
        'author' => array(
            'label' => 'author',
            'class' => 'model_user',
            'other_field' => 'book'
        ),
    );

    var $has_many = array(
        'borrowed_user_list' => array(
            'label' => 'Borrowed users',
            'class' => 'model_user',
            'other_field' => 'rental_list',
        ),
    );

    // Custom Validation
    function _build_code($field) {
        if (!empty($this->{$field})) {
            $this->{$field} = sha1(md5(uniqid($this->{$field}, true)));
        }
    }
}
```

***Admin Controller***
```
class Admin extends MDI_Admin_Controller {
    var $dashboard = array(
        'group 1' => array(
            'Model_User',
            'Model_Book',
        ),

...
...
```

Now, when you login to administrator panel or you use its model class then ***automatically will generate the following tables*** :
```
users
books
books_users
```
> 'books_user' table is m2m junction table

### Usage of Model

```
// Example for create admin
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
```
In the above example, the `MDI_Credential_Native` is a MDI basic model that contains account information. 
its has One to One(O2O) relationships with `MDI_User`.

***Other details, Please refer to the documents [Datamapper ORM](http://datamapper.wanwizard.eu/pages/gettingstarted.html)***

### The difference between the Datamapper ###
In Preparing

## Administrator Panel
In Preparing

## Controller Annotation
In Preparing

# License

###[CodeIgniter](http://www.codeigniter.com/userguide2/license.html)
- Copyright © 2008-2011 Ellislab, Inc.
- Copyright (c) 2014 - 2015, British Columbia Institute of Technology
All rights reserved.

###[DataMapper](http://datamapper.wanwizard.eu/pages/license.html)
- Copyright © 2010-2011 Harro "WanWizard" Verton
- Copyright © 2009-2010 Phil DeJarnett
- Based on the original DataMapper, Copyright © 2008 Simon Stenhouse

###Other
- Licensed under the MIT license:
- [Http://www.opensource.org/licenses/MIT](Http://www.opensource.org/licenses/MIT)
