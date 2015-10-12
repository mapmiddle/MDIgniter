<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$mdiclass = <<<CODE
class MDI_Hooks extends $name
{
	function _call_hook(\$which = '')
	{
        /*
            if (strcasecmp(\$which, 'pre_controller') == 0) {
                require_once(APPPATH.'libraries/mdi/mdi.php');
                mdi::pre_controller();
            }
	    */

	    return parent::_call_hook(\$which);
	}

}
CODE;

// dynamically add our class extension
eval($mdiclass);
unset($mdiclass);

// and update the name of the class to instantiate
$name = 'MDI_Hooks';

/* End of file Hooks.php */
/* Location: ./application/third_party/mdi/system/Hooks.php */
