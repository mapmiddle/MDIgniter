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

# Documentation
# Example
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
