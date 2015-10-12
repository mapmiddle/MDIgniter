<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$mdiclass = <<<CODE
class MDI_Input extends $name
{
	function _fetch_from_array_default(&\$array, \$index = '', \$default = NULL, \$xss_clean = FALSE)
	{
		if ( ! isset(\$array[\$index]))
		{
			return \$default;
		}

		if (\$xss_clean === TRUE)
		{
			return \$this->security->xss_clean(\$array[\$index]);
		}

		return \$array[\$index];
	}

	function get_default(\$index, \$default, \$xss_clean = FALSE)
	{
		return \$this->_fetch_from_array_default(\$_GET, \$index, \$default, \$xss_clean);
	}

	function post_default(\$index, \$default, \$xss_clean = FALSE)
	{
		return \$this->_fetch_from_array_default(\$_POST, \$index, \$default, \$xss_clean);
	}
}
CODE;

// dynamically add our class extension
eval($mdiclass);
unset($mdiclass);

// and update the name of the class to instantiate
$name = 'MDI_Input';

/* End of file Input.php */
/* Location: ./application/third_party/mdi/system/Input.php */
