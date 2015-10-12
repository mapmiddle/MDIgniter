<?php


require_once BASEPATH.'libraries/User_agent.php';

class MDI_User_agent extends CI_User_agent {

    var $is_desktop = FALSE;
    var $is_mobile_app = FALSE;

    var $mobile_apps = array();
    var $mobile_app = '';

    public function __construct()
    {
        if (isset($_SERVER['HTTP_USER_AGENT']))
        {
            $this->agent = trim($_SERVER['HTTP_USER_AGENT']);
        }

        if ( ! is_null($this->agent))
        {
            if ($this->_load_agent_file())
            {
                $this->_compile_data();
            }
        }

        log_message('debug', "MDI User Agent Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
     * Compile the User Agent Data
     *
     * @access	private
     * @return	bool
     */
    private function _load_agent_file()
    {
        if (defined('ENVIRONMENT') AND is_file(APPPATH.'config/'.ENVIRONMENT.'/user_agents.php'))
        {
            include(APPPATH.'config/'.ENVIRONMENT.'/user_agents.php');
        }
        else if (is_file(APPPATH.'config/user_agents.php'))
        {
            include(APPPATH.'config/user_agents.php');
        }
        else
        {
            return FALSE;
        }

        if (defined('ENVIRONMENT') AND is_file(APPPATH.'config/'.ENVIRONMENT.'/mdi/mdi_user_agents.php'))
        {
            include(APPPATH.'config/'.ENVIRONMENT.'/mdi/mdi_user_agents.php');
        }
        else if (is_file(APPPATH.'config/mdi/mdi_user_agents.php'))
        {
            include(APPPATH.'config/mdi/mdi_user_agents.php');
        }
        else
        {
            return FALSE;
        }


        $return = FALSE;

        if (isset($platforms))
        {
            $this->platforms = $platforms;
            unset($platforms);
            $return = TRUE;
        }

        if (isset($browsers))
        {
            $this->browsers = $browsers;
            unset($browsers);
            $return = TRUE;
        }

        if (isset($mobiles))
        {
            $this->mobiles = $mobiles;
            unset($mobiles);
            $return = TRUE;
        }

        if (isset($robots))
        {
            $this->robots = $robots;
            unset($robots);
            $return = TRUE;
        }

        if (isset($mobile_apps))
        {
            $this->mobile_apps = $mobile_apps;

            if (isset($this->mobiles)) {
                $this->mobiles = $mobile_apps + $this->mobiles;
            }

            unset($mobile_apps);
            $return = TRUE;
        }

        return $return;
    }

    // --------------------------------------------------------------------

    /**
     * Compile the User Agent Data
     *
     * @access	private
     * @return	bool
     */
    private function _compile_data()
    {
        $this->_set_platform();

        foreach (array('_set_robot', '_set_browser', '_set_mobile') as $function)
        {
            if ($this->$function() === TRUE)
            {
                break;
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Set the Platform
     *
     * @access	private
     * @return	mixed
     */
    private function _set_platform()
    {
        if (is_array($this->platforms) AND count($this->platforms) > 0)
        {
            foreach ($this->platforms as $key => $val)
            {
                if (preg_match("|".preg_quote($key)."|i", $this->agent))
                {
                    $this->platform = $val;
                    return TRUE;
                }
            }
        }
        $this->platform = 'Unknown Platform';
    }

    // --------------------------------------------------------------------

    /**
     * Set the Browser
     *
     * @access	private
     * @return	bool
     */
    private function _set_browser()
    {
        if (is_array($this->browsers) AND count($this->browsers) > 0)
        {
            foreach ($this->browsers as $key => $val)
            {
                if (preg_match("|".preg_quote($key).".*?([0-9\.]+)|i", $this->agent, $match))
                {
                    $this->is_desktop = TRUE;
                    $this->is_browser = TRUE;
                    $this->version = $match[1];
                    $this->browser = $val;
                    $this->_set_mobile();
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Set the Robot
     *
     * @access	private
     * @return	bool
     */
    private function _set_robot()
    {
        if (is_array($this->robots) AND count($this->robots) > 0)
        {
            foreach ($this->robots as $key => $val)
            {
                if (preg_match("|".preg_quote($key)."|i", $this->agent))
                {
                    $this->is_robot = TRUE;
                    $this->robot = $val;
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Set the Mobile Device
     *
     * @access	private
     * @return	bool
     */
    private function _set_mobile()
    {
        if (is_array($this->mobiles) AND count($this->mobiles) > 0)
        {
            foreach ($this->mobiles as $key => $val)
            {
                if (FALSE !== (strpos(strtolower($this->agent), $key)))
                {
                    $this->is_desktop = FALSE;
                    $this->is_mobile = TRUE;
                    $this->mobile = $val;
                    $this->_set_mobile_apps();
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * Set the Mobile Application
     *
     * @access	private
     * @return	bool
     */
    private function _set_mobile_apps()
    {
        if (is_array($this->mobile_apps) AND count($this->mobile_apps) > 0)
        {
            foreach ($this->mobile_apps as $key => $val)
            {
                if (FALSE !== (strpos(strtolower($this->agent), $key)))
                {
                    $this->mobile_app = $val;
                    $this->is_mobile_app = TRUE;
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    // --------------------------------------------------------------------

    public function is_desktop($key = NULL) {
        if (!$this->is_desktop) {
            return FALSE;
        }

        // No need to be specific
        if ($key === NULL)
        {
            return TRUE;
        }

        // Check for a specific
        return $this->is_browser($key);
    }

    public function is_mobile_app($key = NULL) {
        if (!$this->is_mobile_app) {
            return FALSE;
        }

        // No need to be specific
        if ($key === NULL)
        {
            return TRUE;
        }

        // Check for a specific
        return array_key_exists($key, $this->mobile_apps) AND $this->mobile_app === $this->mobile_apps[$key];
    }

    public function is_mobile_web($key = NULL) {
        if (!$this->is_mobile || !$this->is_browser) {
            return FALSE;
        }

        // No need to be specific
        if ($key === NULL)
        {
            return TRUE;
        }

        // Check for a specific
        return $this->is_browser($key);
    }

    public function is_mobile_app_android() {
        if ($this->mobile_app == 'android') {
            return TRUE;
        }

        return FALSE;
    }

    public function is_mobile_app_ios() {
        if ($this->mobile_app == 'ios') {
            return TRUE;
        }

        return FALSE;
    }

    public function get_mobile_app_agent($device) {
        $agents = array_flip($this->mobile_apps);
        return $agents[$device];
    }
}