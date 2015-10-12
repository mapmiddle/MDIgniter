<?php


require_once(APPPATH.'libraries/mdi/libs/jqueryfileupload/uploadhandler.php');

class FileUploadHandler extends UploadHandler {

    function __construct($options = null, $initialize = true, $error_messages = null) {
        parent::__construct($options, $initialize, $error_messages);
    }

    protected function set_additional_file_properties($file) {
        // blank
    }

    public function prepopulated() {
        $url_paths = $this->get_prepopulated_url_path_params();
        $response = array();

        if (!empty($url_paths)) {
            foreach($url_paths as $url_path) {
                // cut off this hostname
                if (parse_url(base_url(), PHP_URL_HOST) === parse_url($url_path, PHP_URL_HOST)) {
                    $url_path = ltrim(parse_url($url_path, PHP_URL_PATH), '/');
                }

                $response[] = $url_path;
            }
        }

        return $response;
    }

    public function delete($print_response = true) {
        $file_paths = $this->get_delete_file_params();
        $response = array();

        if (!empty($file_paths)) {
            foreach($file_paths as $file_path) {
                if (parse_url(base_url(), PHP_URL_HOST) === parse_url($file_path, PHP_URL_HOST)) {
                    $file_path = ltrim(parse_url($file_path, PHP_URL_PATH), '/');
                    $file_name = basename(stripslashes($file_path));

                    $success = is_file($file_path) && unlink($file_path);
                    if ($success) {
                        foreach($this->options['image_versions'] as $version => $options) {
                            if (!empty($version)) {
                                // middle.FIXME
                                // recursive directory delete
                                $file = $this->get_upload_path($file_name, $version);
                                if (is_file($file)) {
                                    unlink($file);
                                }
                            }
                        }
                    }
                } else {
                    $success = FALSE;
                }

                $response[$file_path] = $success;
            }
        }

        return $this->generate_response($response, $print_response);
    }

    protected function handle_image_file($file_path, $file) {
        $failed_versions = array();
        foreach($this->options['image_versions'] as $version => $options) {
            //middle.insert
            if (isset($options['preprocess'])) {
                if (function_exists($options['preprocess'])) {
                    $options['preprocess']($options);
                }
            }

            if ($this->create_scaled_image($file->name, $version, $options)) {
                if (!empty($version)) {
                    $file->{$version.'Url'} = $this->get_download_url(
                        $file->name,
                        $version
                    );
                } else {
                    $file->size = $this->get_file_size($file_path, true);
                }

                //middle.insert
                if (isset($options['postprocess'])) {
                    if (function_exists($options['postprocess'])) {
                        $options['postprocess']($file->name);
                    }
                }
            } else {
                $failed_versions[] = $version ? $version : 'original';
            }
        }
        if (count($failed_versions)) {
            $file->error = $this->get_error_message('image_resize')
                .' ('.implode($failed_versions,', ').')';
        }
        // Free memory:
        $this->destroy_image_object($file_path);
    }

    protected function get_delete_file_params() {
        $param_name = $this->options['param_name'] . '-delete';
        $params = $this->get_post_query_param($param_name);
        if (!$params) {
            return null;
        }

        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $params[$key] = stripslashes($value);
            }
        } else if (is_string($params)) {
            $params = array(0 => stripslashes($params));
        } else {
            return NULL;
        }

        return $params;
    }
    protected function get_delete_file_names_params() {
        $param_name = $this->options['param_name'] . '-delete';
        $params = $this->get_post_query_param($param_name);
        if (!$params) {
            return null;
        }

        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $params[$key] = basename(stripslashes($value));
            }
        } else if (is_string($params)) {
            $params = array(0 => basename(stripslashes($params)));
        } else {
            return NULL;
        }

        return $params;
    }

    protected function get_prepopulated_url_path_params() {
        $param_name = $this->options['param_name'] . '-prepopulated';
        $params = $this->get_post_query_param($param_name);
        if (!$params) {
            return NULL;
        }

        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $params[$key] = $value;
            }
        } else if (is_string($params)) {
            $params = array(0 => $params);
        } else {
            return NULL;
        }

        return $params;
    }

    protected function trim_file_name($file_path, $name, $size, $type, $error,
                                      $index, $content_range) {

        if (isset($this->options['file_name']) && $this->options['file_name']) {
            $name = $this->options['file_name'];
        } else {
            // Remove path information and dots around the filename, to prevent uploading
            // into different directories or replacing hidden system files.
            // Also remove control characters and spaces (\x00..\x20) around the filename:
            $name = trim(basename(stripslashes($name)), ".\x00..\x20");
        }

        if (isset($this->options['file_name_enc']) && $this->options['file_name_enc']) {
            $name = md5($name);
        }

        if (isset($this->options['file_name_timestamp']) && $this->options['file_name_timestamp']) {
            $dt = new DateTime();
            $name = $dt->format('ymdHis').'_'.$name;
        }

        return $name;
    }

    protected function get_post_query_param($id) {
        if (isset($_GET[$id])) {
            return $_GET[$id];
        }

        return @$_POST[$id];
    }

    public function get_image_size($file_path, $auto_orient=TRUE) {
        list($img_width, $img_height) = parent::get_image_size($file_path);

        if ($auto_orient &&
            function_exists('exif_read_data') &&
            ($exif = @exif_read_data($file_path)) &&
            (((int) @$exif['Orientation']) >= 5 )
        ) {
            $tmp = $img_width;
            $img_width = $img_height;
            $img_height = $tmp;
            unset($tmp);
        }

        return array($img_width, $img_height);
    }
};

class FileUploader {
    public function __construct() {
        // blank
    }

    function statics_lazy() {
        $ci =& get_instance();
        $ci->mdi->statics_lazy('css', 'mdi/bootstrap/css/bootstrap.min.css');
        $ci->mdi->statics_lazy('css', 'mdi/bootstrap-jqueryfileupload/css/jquery.fileupload-ui.css');
        $ci->mdi->statics_lazy('css', 'mdi/bootstrap-mdi/bootstrap-mdi-fileuploader.css');
        $ci->mdi->statics_lazy('js', 'mdi/bootstrap/js/bootstrap.min.js');
        $ci->mdi->statics_lazy('js', 'mdi/bootstrap-jqueryfileupload/js/vendor/jquery.ui.widget.js');
        $ci->mdi->statics_lazy('js', 'mdi/bootstrap-jqueryfileupload/js/cors/jquery.load-image.all.min.js');
        $ci->mdi->statics_lazy('js', 'mdi/bootstrap-jqueryfileupload/js/cors/jquery.canvas-to-blob.min.js');
        $ci->mdi->statics_lazy('js', 'mdi/bootstrap-jqueryfileupload/js/jquery.iframe-transport.js');
        $ci->mdi->statics_lazy('js', 'mdi/bootstrap-jqueryfileupload/js/jquery.fileupload.js');
        $ci->mdi->statics_lazy('js', 'mdi/bootstrap-jqueryfileupload/js/jquery.fileupload-process.js');
        $ci->mdi->statics_lazy('js', 'mdi/bootstrap-jqueryfileupload/js/jquery.fileupload-validate.js');
        $ci->mdi->statics_lazy('js', 'mdi/bootstrap-jqueryfileupload/js/jquery.fileupload-image.js');
        $ci->mdi->statics_lazy('js', 'mdi/bootstrap-mdi/bootstrap-mdi-fileuploader.js');
        $ci->mdi->statics_lazy('js', 'mdi/bootstrap-mdi/bootstrap-mdi-fileuploader-widget.js');
    }

    function process($kwargs=array()) {
        return array(
            'prepopulated' => $this->prepopulated($kwargs),
            'delete' => $this->delete($kwargs),
            'upload' => $this->upload($kwargs),
        );
    }

    function prepopulated($kwargs=array()) {
        $result = array();
        $ci =& get_instance();
        switch ($ci->input->server('REQUEST_METHOD')) {
            case 'GET':
            case 'PATCH':
            case 'PUT':
            case 'POST':
                break;
            default:
                return $result;
        };

        $options = $this->_options($kwargs);
        $handler = new FileUploadHandler($options, FALSE);
        $response = $handler->prepopulated();

        return $response;
    }

    function upload($kwargs=array()) {
        $result = array();
        $ci =& get_instance();
        switch ($ci->input->server('REQUEST_METHOD')) {
            case 'PATCH':
            case 'PUT':
            case 'POST':
                break;
            default:
                return $result;
        };

        error_reporting(E_ALL | E_STRICT);
        $options = $this->_options($kwargs);
        $handler = new FileUploadHandler($options, FALSE);
        $response = $handler->post(FALSE);

        $param_name = $options['param_name'];
        foreach ($response[$param_name] as $value) {
            $result[] = $value;
        }

        return $result;
    }

    function delete($kwargs=array()) {
        $result = array();
        $ci =& get_instance();
        switch ($ci->input->server('REQUEST_METHOD')) {
            case 'GET':
            case 'DELETE':
            case 'PATCH':
            case 'PUT':
            case 'POST':
                break;
            default:
                return $result;
        };

        error_reporting(E_ALL | E_STRICT);
        $options = $this->_options($kwargs);
        $handler = new FileUploadHandler($options, FALSE);
        $response = $handler->delete(FALSE);

        foreach ($response as $key => $value) {
            $result[] = $key;
        }

        return $result;
    }

    protected function _options($kwargs=array()) {
        $name = array_get($kwargs['name'], 'files');
        $dir = mdi::config('media'). array_get($kwargs['dir'], 'tmp/');
        $filename = array_get($kwargs['filename'], NULL);
        $filename_enc = array_get($kwargs['filename_enc'], TRUE);
        $filename_timestamp = array_get($kwargs['filename_timestamp'], TRUE);
        $image_only = array_get($kwargs['image_only'], FALSE);
        $image_versions = array_get($kwargs['image_versions'], $this->get_default_image_versions());

        if ($this->_is_filename_array($name)) {
            $name = substr($name, 0, -2);
        }

        if ($image_only) {
            $accept_file_types = '/\.(gif|jpe?g|png|svg)$/i';
        } else {
            $accept_file_types = '/.+$/i';
        }

        return array(
            'param_name' => $name,
            'file_name' => $filename,
            'file_name_enc' => $filename_enc,
            'file_name_timestamp' => $filename_timestamp,
            'upload_dir' => $dir,
            'upload_url' => base_url().$dir,
            'accept_file_types' => $accept_file_types,
            'image_versions' => $image_versions,
        );
    }

    function input($kwargs=array()) {
        $name = array_get($kwargs['name'], 'files[]');
        $single_file = array_get($kwargs['single_file'], FALSE);
        $multiple = array_get($kwargs['multiple'], FALSE);
        $async = array_get($kwargs['async'], FALSE);
        $async_url = array_get($kwargs['async_url'], NULL);
        $async_upload_immediately = array_get($kwargs['async_upload_immediately'], FALSE);
        $image_only = array_get($kwargs['image_only'], FALSE);
        $prepopulated = array_get($kwargs['prepopulated'], array());
        $max_number_of_files = array_get($kwargs['max_number_of_files'], NULL);

        if ($image_only) {
            $btn_text = 'Add Image'; ///
        } else {
            $btn_text = 'Add File'; ///
        }

        if ($single_file) {
            if ($this->_is_filename_array($name)) {
                $name = substr($name, 0, -2);
            }
        } else {
            if (!$this->_is_filename_array($name)) {
                $name = $name.'[]';
            }
        }

        if (is_string($prepopulated)) {
            $prepopulated = preg_split( '/(;|\s)/', $prepopulated, -1, PREG_SPLIT_NO_EMPTY);
            array_walk($prepopulated, array($this, '_url_media_validation'), $prepopulated);
            $prepopulated = json_encode($prepopulated);
        } else if (is_array($prepopulated) && !empty($prepopulated)) {
            array_walk($prepopulated, array($this, '_url_media_validation'), $prepopulated);
            $prepopulated = json_encode($prepopulated);
        } else {
            $prepopulated = NULL;
        }

        $class_name = 'mdi-fileuploader-container';
        if ($single_file) {
            $class_name .= ' single';
        }

        $input = <<<CODE
        <div class="$class_name">
CODE;
        if ($prepopulated) {
            $input .= "<div class=\"mdi-fileuploader-prepopulated\">";
            $input .= $prepopulated;
            $input .= "</div>";
        }

        $input .= <<<CODE
            <div class="mdi-fileuploader-preview">
            </div>
            <span class="btn btn-success mdi-fileuploader-button">
                <i class="glyphicon glyphicon-plus"></i>
                <span>$btn_text</span>
                <input class="mdi-fileuploader-input" type="file" name="$name"
CODE;
        $input .= $single_file ? "data-single-file=\"$single_file\"" : '';
        $input .= $multiple ? "data-multiple=\"$multiple\"" : '';
        $input .= $async ? "data-async=\"$async\"" : '';
        $input .= $async_url ? "data-async-url=\"$async_url\"" : '';
        $input .= $async_upload_immediately ? "data-async-upload-immediately=\"$async_upload_immediately\"" : '';
        $input .= $image_only ? "data-image-only=\"$image_only\"" : '';
        $input .= $max_number_of_files ? "data-max-number-of-files=\"$max_number_of_files\"" : '';
        $input .= <<<CODE
                >
            </span>
        </div>
CODE;

        echo $input;
    }

    public function get_image_size($paths, $auto_orient=TRUE, $force_array=FALSE) {
        if (is_string($paths)) {
            $path_array = preg_split( '/(;|\s)/', $paths, -1, PREG_SPLIT_NO_EMPTY);
            $is_array = FALSE;
        } else if(is_array($paths)) {
            $path_array = $paths;
            $is_array = TRUE;
        } else {
            return NULL;
        }

        $handler = new FileUploadHandler($this->_options(array()), FALSE);
        $current_host = parse_url(base_url(), PHP_URL_HOST);
        $new_path_array = array();

        foreach ($path_array as $path) {
            $path_host = (parse_url($path, PHP_URL_HOST));

            if ($path_host && $current_host !== $path_host) {
                $new_path_array[] = $path;
            } else {
                $new_path_array[] = ltrim(stripslashes(parse_url($path, PHP_URL_PATH)), '/');
            }
        }

        if ($force_array || $is_array) {
            $result = array();
            foreach($new_path_array as $path) {
                $result[] = @$handler->get_image_size($path, $auto_orient);
            }
        } else {
            $result = @$handler->get_image_size($new_path_array[0], $auto_orient);
        }

        return $result;
    }

    public function get_sub_image_path($sub_dir_name, $paths) {
        if (is_string($paths)) {
            $path_array = preg_split( '/(;|\s)/', $paths, -1, PREG_SPLIT_NO_EMPTY);
        } else if(is_array($paths)) {
            $path_array = $paths;
        } else {
            return NULL;
        }

        $current_host = parse_url(base_url(), PHP_URL_HOST);
        $result_array = array();

        foreach ($path_array as $path) {
            $path_host = (parse_url($path, PHP_URL_HOST));
            if ($path_host && $current_host !== $path_host) {
                $result_array[] = $path;
            } else {
                $basename = basename(stripslashes($path));
                $dirname  = ltrim(dirname(stripslashes($path)), '/');
                $result_array[] = $dirname.'/'.$sub_dir_name.'/'.$basename;
            }
        }

        if (is_string($paths)) {
            return implode('; ', $result_array);
        }

        return $result_array;
    }

    public function make_value($default_values, $process_response) {
        $result_array = preg_split( '/(;|\s)/', $default_values, -1, PREG_SPLIT_NO_EMPTY);
        $result_array = array_flip($result_array);

        if (array_key_exists('prepopulated', $process_response)) {
            foreach ($process_response['prepopulated'] as $value) {
                $result_array[$value] = TRUE;
            }
        }

        if (array_key_exists('upload', $process_response)) {
            foreach ($process_response['upload'] as $value) {
                if (property_exists($value, 'url')) {
                    $url = ltrim(parse_url($value->url, PHP_URL_PATH), '/');
                    $result_array[$url] = TRUE;
                }
            }
        }

        if (array_key_exists('delete', $process_response)) {
            foreach ($process_response['delete'] as $value) {
                unset($result_array[$value]);
            }
        }

        $result = '';
        foreach($result_array as $key => $value) {
            $result .= $key.'; ';
        }

        if (empty($result)) {
            $result = NULL;
        }

        return $result;
    }

    public function as_json($value, $sub_dir_name='', $force_array=FALSE) {
        // middle.comment
        // if $force_array value is FALSE
        // return to string

        $result_array = preg_split( '/(;|\s)/', $value, -1, PREG_SPLIT_NO_EMPTY);

        if ($force_array) {
            if (!empty($result_array)) {
                $result = $result_array;
            } else {
                return array();
            }
        } else {
            if (!empty($result_array)) {
                if (count($result_array) > 1) {
                    $result = $result_array;
                } else {
                    $result = $result_array[0]; //.';';
                }
            } else {
                return '';
            }
        }


        if (empty($sub_dir_name)) {
            $url = $result;
        } else {
            $url = $this->get_sub_image_path($sub_dir_name, $result);
        }

        if (is_array($url)) {
            foreach ($url as &$url_item) {
                $url_item = base_url().trim($url_item, '; ');
            }
        } else if (!empty($url)) {
            $url = base_url().trim($url, '; ');
        }

        return $url;
    }

    public function get_default_image_versions() {
        $ci =& get_instance();
        $image_versions = array(
            '' => array(
                'auto_orient' => true
            ),

            'thumbnail' => array(
                //'crop' => true,
                'max_width' => $ci->mdi->config('fileuploader_thumbnail_image_max_width'),
                'max_height' => $ci->mdi->config('fileuploader_thumbnail_image_max_height')
            )
        );

        return $image_versions;
    }

    protected function _url_media_validation(&$item, $key, $array) {
        if(!filter_var($array[$key], FILTER_VALIDATE_URL)) {
            $item = base_url().$array[$key];
        }
    }

    protected function _is_filename_array($name) {
        return preg_match('/^.+\[\]$/i', $name);
    }
};