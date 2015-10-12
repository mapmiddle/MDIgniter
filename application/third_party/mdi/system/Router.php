<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$mdiclass = <<<CODE
class MDI_Router extends $name
{
	function reverse_site_url(\$uri = '')
    {
        \$Router =& load_class('Router');

        // \$uri is expected to be a string, in the form of controller/function/param1
        // trim leading and trailing slashes, just in case
        \$uri = trim(\$uri,'/');

        \$routes = \$Router->routes;
        \$reverseRoutes = array_flip( \$routes );

        unset( \$routes['default_controller'], \$routes['scaffolding_trigger'] );

        // Loop through all routes to check for back-references, then see if the user-supplied URI matches one
        foreach (\$routes as \$key => \$val)
        {
            // bailing if route contains ungrouped regex, otherwise this fails badly
            if( preg_match( '/[^\\(][.+?{\\:]/', \$key ) )
                continue;

            // Do we have a back-reference?
            if (strpos(\$val, '$') !== FALSE AND strpos(\$key, '(') !== FALSE)
            {
                // Find all back-references in custom route and CI route
                preg_match_all( '/\\(.+?\\)/', \$key, \$keyRefs );
                preg_match_all( '/\\$.+?/', \$val, \$valRefs );

                \$keyRefs = \$keyRefs[0];

                // Create URI Regex, to test passed-in uri against a custom route's CI ( standard ) route
                \$uriRegex = \$val;

                // Extract positional parameters (backreferences), and order them such that
                // the keys of \$goodValRefs dirrectly mirror the correct value in \$keyRefs
                \$goodValRefs = array();
                foreach (\$valRefs[0] as \$ref) {
                    \$tempKey = substr(\$ref, 1);
                    if (is_numeric(\$tempKey)) {
                        --\$tempKey;
                        \$goodValRefs[\$tempKey] = \$ref;
                    }
                }

                // Replaces back-references in CI route with custom route's regex [ $1 replaced with (:num), for example ]
                foreach (\$goodValRefs as \$tempKey => \$ref) {
                    if (isset(\$keyRefs[\$tempKey])) {
                        \$uriRegex = str_replace(\$ref, \$keyRefs[\$tempKey], \$uriRegex);
                    }
                }

                // replace :any and :num with .+ and [0-9]+, respectively
                \$uriRegex = str_replace(':any', '.+', str_replace(':num', '[0-9]+', \$uriRegex));

                // regex creation is finished.  Test it against uri
                if (preg_match('#^'.\$uriRegex.'$#', \$uri)){
                    // A match was found.  We can now build the custom URI

                    // We need to create a custom route back-referenced regex, to plug user's uri params into the new routed uri.
                    // First, find all custom route strings between capture groups
                    \$key = str_replace(':any', '.+', str_replace(':num', '[0-9]+', \$key));

                    \$routeString = preg_split( '/\\(.+?\\)/', \$key );

                    // build regex using original CI route's back-references
                    \$replacement = '';
                    \$rsEnd = count( \$routeString ) - 1;

                    // merge route strings with original back-references, 1-for-1, like a zipper
                    for( \$i = 0; \$i < \$rsEnd; \$i++ ){
                        \$replacement .= \$routeString[\$i] . \$valRefs[0][\$i];
                    }
                    \$replacement .= \$routeString[\$rsEnd];

                    /*
                             At this point,our variables are defined as:
                                \$uriRegex:        regex to match against user-supplied URI
                                \$replacement:    custom route regex, replacing capture-groups with back-references

                            All that's left to do is create the custom URI, and return the site_url
                    */
                    \$customURI = preg_replace( '#^'.\$uriRegex.'$#', \$replacement, \$uri );

                    return site_url( \$customURI );
                }

            }
            // If there is a literal match AND no back-references are setup, and we are done
            else if(\$val == \$uri)

                return site_url( \$key );
        }

        return site_url( \$uri );
    }

    function _validate_request(\$segments)
    {
        if (count(\$segments) == 0)
		{
			return \$segments;
		}

		// Does the requested controller exist in the root folder?
		if (file_exists(APPPATH.'controllers/'.\$segments[0].'.php'))
		{
			return \$segments;
		}

		// Is the controller in a sub-folder?
		if (is_dir(APPPATH.'controllers/'.\$segments[0]))
		{
			// Set the directory and remove it from the segment array
			\$this->set_directory(\$segments[0]);
			\$segments = array_slice(\$segments, 1);

            /* ----------- ADDED CODE ------------ */

            while(count(\$segments) > 0 && is_dir(APPPATH.'controllers/'.\$this->directory.\$segments[0]))
            {
                // Set the directory and remove it from the segment array
                \$this->directory = \$this->directory . \$segments[0] . '/';
                \$segments = array_slice(\$segments, 1);
            }

            /* ----------- END ------------ */

			if (count(\$segments) > 0)
			{
				// Does the requested controller exist in the sub-folder?
				if ( ! file_exists(APPPATH.'controllers/'.\$this->fetch_directory().\$segments[0].'.php'))
				{
					if ( ! empty(\$this->routes['404_override']))
					{
						\$x = explode('/', \$this->routes['404_override']);

						\$this->set_directory('');
						\$this->set_class(\$x[0]);
						\$this->set_method(isset(\$x[1]) ? \$x[1] : 'index');

						return \$x;
					}
					else
					{
						show_404(\$this->fetch_directory().\$segments[0]);
					}
				}
			}
			else
			{
				// Is the method being specified in the route?
				if (strpos(\$this->default_controller, '/') !== FALSE)
				{
					\$x = explode('/', \$this->default_controller);

					\$this->set_class(\$x[0]);
					\$this->set_method(\$x[1]);
				}
				else
				{
					\$this->set_class(\$this->default_controller);
					\$this->set_method('index');
				}

				// Does the default controller exist in the sub-folder?
				if ( ! file_exists(APPPATH.'controllers/'.\$this->fetch_directory().\$this->default_controller.'.php'))
				{
					\$this->directory = '';
					return array();
				}

			}

			return \$segments;
		}


		// If we've gotten this far it means that the URI does not correlate to a valid
		// controller class.  We will now see if there is an override
		if ( ! empty(\$this->routes['404_override']))
		{
			\$x = explode('/', \$this->routes['404_override']);

			\$this->set_class(\$x[0]);
			\$this->set_method(isset(\$x[1]) ? \$x[1] : 'index');

			return \$x;
		}


		// Nothing else to do at this point but show a 404
		show_404(\$segments[0]);
    }

}
CODE;

// dynamically add our class extension
eval($mdiclass);
unset($mdiclass);

// and update the name of the class to instantiate
$name = 'MDI_Router';

/* End of file Router.php */
/* Location: ./application/third_party/mdi/system/Router.php */
