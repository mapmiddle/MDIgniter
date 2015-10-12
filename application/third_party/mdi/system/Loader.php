<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$mdiclass = <<<CODE
class MDI_Loader extends $name
{
    var \$controller = NULL;

	public function __construct() {
	    parent::__construct();
	    \$this->controller = CI_Controller::get_instance();
        \$this->add_package_path(APPPATH.'third_party/mdi/system/');
	}

    //public function get_controller(
    public function is_model_loaded(\$name) {
        return in_array(\$name, \$this->_ci_models, TRUE);
    }

	public function model_include(\$model, \$name = '', \$db_conn = FALSE)
	{
		if (is_array(\$model))
		{
			foreach (\$model as \$babe)
			{
				\$this->model(\$babe);
			}
			return;
		}

		if (\$model == '')
		{
			return;
		}

		\$path = '';

		// Is the model in a sub-folder? If so, parse out the filename and path.
		if ((\$last_slash = strrpos(\$model, '/')) !== FALSE)
		{
			// The path is in front of the last slash
			\$path = substr(\$model, 0, \$last_slash + 1);

			// And the model name behind it
			\$model = substr(\$model, \$last_slash + 1);
		}

		if (\$name == '')
		{
			\$name = \$model;
		}

		if (in_array(\$name, \$this->_ci_models, TRUE))
		{
			return;
		}

		\$CI =& get_instance();
		if (isset(\$CI->\$name))
		{
			show_error('The model name you are loading is the name of a resource that is already being used: '.$name);
		}

		\$model = strtolower(\$model);

		foreach (\$this->_ci_model_paths as \$mod_path)
		{
			if ( ! file_exists(\$mod_path.'models/'.\$path.\$model.'.php'))
			{
				continue;
			}

			if (\$db_conn !== FALSE AND ! class_exists('CI_DB'))
			{
				if (\$db_conn === TRUE)
				{
					\$db_conn = '';
				}

				\$CI->load->database(\$db_conn, FALSE, TRUE);
			}

			if ( ! class_exists('CI_Model'))
			{
				load_class('Model', 'core');
			}

			if ( ! class_exists(ucfirst(\$model)))
			{
                require_once(\$mod_path.'models/'.\$path.\$model.'.php');
			}
			return;
		}

		// couldn't find the model
		show_error('Unable to locate the model you have specified: '.\$model);
	}

	/**
	 * Load the Database Forge Class
	 *
	 * @return	string
	 */
	public function dbforge()
	{
		if ( ! class_exists('CI_DB'))
		{
			\$this->database();
		}

		\$CI =& get_instance();


		require_once(BASEPATH.'database/DB_forge.php');
		require_once(BASEPATH.'database/drivers/'.\$CI->db->dbdriver.'/'.\$CI->db->dbdriver.'_forge.php');

        if (file_exists(\$file = APPPATH.'third_party/mdi/system/database/drivers/'.\$CI->db->dbdriver.'/'.\$CI->db->dbdriver.'_forge.php')) {
			require_once(APPPATH.'third_party/mdi/system/database/drivers/'.\$CI->db->dbdriver.'/'.\$CI->db->dbdriver.'_forge.php');
			\$class = 'MDI_DB_'.\$CI->db->dbdriver.'_forge';
        } else {
			\$class = 'CI_DB_'.\$CI->db->dbdriver.'_forge';
		}

		\$CI->dbforge = new \$class();
	}
}
CODE;

// dynamically add our class extension
eval($mdiclass);
unset($mdiclass);

// and update the name of the class to instantiate
$name = 'MDI_Loader';

/* End of file Loader.php */
/* Location: ./application/third_party/mdi/datamapper/system/Loader.php */
