<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Data Mapper ORM bootstrap
 *
 * Dynamic CI Loader class extension
 *
 * @license 	MIT License
 * @package		DataMapper ORM
 * @category	DataMapper ORM
 * @author  	Harro "WanWizard" Verton
 * @link		http://datamapper.wanwizard.eu/
 * @version 	2.0.0
 */

$dmclass = <<<CODE
class DM_Loader extends $name
{
	// --------------------------------------------------------------------

	/**
	 * Database Loader
	 *
	 * @param	string	the DB credentials
	 * @param	bool	whether to return the DB object
	 * @param	bool	whether to enable active record (this allows us to override the config setting)
	 * @return	object
	 */
	public function database(\$params = '', \$return = FALSE, \$active_record = NULL)
	{
		// Grab the super object
		\$CI =& get_instance();

		// Do we even need to load the database class?
		if (class_exists('CI_DB') AND \$return == FALSE AND \$active_record == NULL AND isset(\$CI->db) AND is_object(\$CI->db))
		{
			return FALSE;
		}

		require_once(APPPATH.'third_party/mdi/datamapper/system/DB.php');

		if (\$return === TRUE)
		{
			return DB(\$params, \$active_record);
		}

		// Initialize the db variable.  Needed to prevent
		// reference errors with some configurations
		\$CI->db = '';

		// Load the DB class
		\$CI->db =& DB(\$params, \$active_record);
	}
	
	protected function _ci_init_class(\$class, \$prefix = '', \$config = FALSE, \$object_name = NULL)
	{
		// Is there an associated config file for this class?  Note: these should always be lowercase
		if (\$config === NULL)
		{
			// Fetch the config paths containing any package paths
			\$config_component = \$this->_ci_get_component('config');

			if (is_array(\$config_component->_config_paths))
			{
				// Break on the first found file, thus package files
				// are not overridden by default paths
				foreach (\$config_component->_config_paths as \$path)
				{
					// We test for both uppercase and lowercase, for servers that
					// are case-sensitive with regard to file names. Check for environment
					// first, global next
					if (defined('ENVIRONMENT') AND file_exists(\$path .'config/'.ENVIRONMENT.'/'.strtolower(\$class).'.php'))
					{
						include(\$path .'config/'.ENVIRONMENT.'/'.strtolower(\$class).'.php');
						break;
					}
					elseif (defined('ENVIRONMENT') AND file_exists(\$path .'config/'.ENVIRONMENT.'/'.ucfirst(strtolower(\$class)).'.php'))
					{
						include(\$path .'config/'.ENVIRONMENT.'/'.ucfirst(strtolower(\$class)).'.php');
						break;
					}
					elseif (file_exists(\$path .'config/'.strtolower(\$class).'.php'))
					{
						include(\$path .'config/'.strtolower(\$class).'.php');
						break;
					}
					elseif (file_exists(\$path .'config/'.ucfirst(strtolower(\$class)).'.php'))
					{
						include(\$path .'config/'.ucfirst(strtolower(\$class)).'.php');
						break;
					}
				}
			}
		}

		if (\$prefix == '')
		{
			if (class_exists('MDI_'.\$class))
			{
				\$name = 'MDI_'.\$class;
			}
			elseif (class_exists('CI_'.\$class))
			{
				\$name = 'CI_'.\$class;
			}
			elseif (class_exists(config_item('subclass_prefix').\$class))
			{
				\$name = config_item('subclass_prefix').\$class;
			}
			else
			{
				\$name = \$class;
			}
		}
		else
		{
			\$name = \$prefix.\$class;
		}

		// Is the class name valid?
		if ( ! class_exists(\$name))
		{
			log_message('error', "Non-existent class: ".\$name);
			show_error("Non-existent class: ".\$class);
		}

		// Set the variable name we will assign the class to
		// Was a custom class name supplied?  If so we'll use it
		\$class = strtolower(\$class);

		if (is_null(\$object_name))
		{
			\$classvar = ( ! isset(\$this->_ci_varmap[\$class])) ? \$class : \$this->_ci_varmap[\$class];
		}
		else
		{
			\$classvar = \$object_name;
		}

		// Save the class name and object name
		\$this->_ci_classes[\$class] = \$classvar;

		// Instantiate the class
		\$CI =& get_instance();
		if (\$config !== NULL)
		{
			\$CI->\$classvar = new \$name(\$config);
		}
		else
		{
			\$CI->\$classvar = new \$name;
		}
	}
}
CODE;

// dynamically add our class extension
eval($dmclass);
unset($dmclass);

// and update the name of the class to instantiate
$name = 'DM_Loader';

/* End of file Loader.php */
/* Location: ./application/third_party/mdi/datamapper/system/Loader.php */
