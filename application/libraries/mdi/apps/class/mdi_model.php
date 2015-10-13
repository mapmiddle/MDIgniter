<?php


class MDI_Model extends DataMapper {
    private static $syncher = NULL;
    private static $mdicommon = array();
    protected static $_ci = NULL;
    protected static $_mdi = NULL;

    protected $original_related_values = array();
    protected $changed_related_values = array();
    protected $transaction_history = array();
    protected $force_save = FALSE;

    var $abstract = FALSE;
    var $created_field = 'created_time';
    var $updated_field = 'updated_time';
    var $default_order_by = array('id' => 'desc');
    var $unique_together = array();
    var $using_history = TRUE;

    public function __construct($id = NULL, $abstract=FALSE) {
        // ci & mdi
        if (!MDI_Model::$_ci) {
            MDI_Model::$_ci =& get_instance();
        }

        if (!MDI_Model::$_mdi) {
            MDI_Model::$_mdi =& MDI_Model::$_ci->mdi;
        }

        // abstract check
        $is_abstract = $abstract ? TRUE : $this->abstract;

        // abstract
        if ($this->abstract == FALSE) {
            if (empty($this->table)) {
                mdi::error("MDI_Model::__construct Error - table did not defined(model:".get_class($this).")");
            }
        }

        // load syncher
        if ($this->syncher == NULL) {
            MDI_Model::$_ci->mdi->load('features/dbsyncher');
            $this->syncher = new DBSyncher();
        }

        //build validation
        $this->build_validation();

        // construct
        if (!$is_abstract) {
            $is_sync = FALSE;
            if (MDI_Model::$_ci->mdi->config('auto_db_sync')) {
                $is_sync = $this->syncher->sync($this);
            }

            parent::__construct($id);

            $this->initialize();

            if ($is_sync) {
                $this->post_sync();
            }
        }
    }

    protected function get_common_key() {
        $this_class = strtolower(get_class($this));

        // this is to ensure that singular is only called once per model
        if(isset(DataMapper::$common[DMZ_CLASSNAMES_KEY][$this_class])) {
            return DataMapper::$common[DMZ_CLASSNAMES_KEY][$this_class];
        } else {
            return singular($this_class);
        }
    }

    protected function build_validation() {
        $common_key = $this->get_common_key();

        if (isset(MDI_Model::$mdicommon[$common_key])) {
            if (isset(MDI_Model::$mdicommon[$common_key]['_VALIDATION'])
            && isset(MDI_Model::$mdicommon[$common_key]['_HAS_ONE'])
            && isset(MDI_Model::$mdicommon[$common_key]['_HAS_MANY'])) {
                $this->validation = MDI_Model::$mdicommon[$common_key]['_VALIDATION'];
                $this->has_one = MDI_Model::$mdicommon[$common_key]['_HAS_ONE'];
                $this->has_many = MDI_Model::$mdicommon[$common_key]['_HAS_MANY'];
                return;
            }
        }

        // inheritance validation and relationship
        foreach (array(get_class($this) => get_class($this)) + class_parents($this) as $class) {
            if ($class == 'MDI_Model') {
                break;
            }

            $vars = get_class_vars($class);
            if (array_key_exists('validation', $vars)) {
                $this->validation = $this->validation + $vars['validation'];
            }

            if (array_key_exists('has_one', $vars)) {
                $this->has_one = $this->has_one + $vars['has_one'];
            }

            if (array_key_exists('has_many', $vars)) {
                $this->has_many = $this->has_many + $vars['has_many'];
            }
        }

        // default pre validation
        $pre_validation = array();
        if (!isset($this->validation['id'])) {
            $pre_validation['id'] = array(
                'label' => 'id',
                'rules' => array('int'),
                'admin' => array('readonly')
            );
        }

        // default post validation
        $post_validation = array();
        if (!empty($this->created_field)) {
            $post_validation[$this->created_field] = array(
                'label' => $this->created_field,
                'rules' => array('datetime'),
                'admin' => array('readonly')
            );
        }

        if (!empty($this->updated_field)) {
            $post_validation[$this->updated_field] = array(
                'label' => $this->updated_field,
                'rules' => array('datetime'),
                'admin' => array('readonly')
            );
        }

        // merge validation
        $this->validation = $pre_validation + $this->validation + $post_validation;

        // save
        if (!isset(MDI_Model::$mdicommon[$common_key])) {
            MDI_Model::$mdicommon[$common_key] = array();
        }

        MDI_Model::$mdicommon[$common_key]['_VALIDATION'] = $this->validation;
        MDI_Model::$mdicommon[$common_key]['_HAS_ONE'] = $this->has_one;
        MDI_Model::$mdicommon[$common_key]['_HAS_MANY'] = $this->has_many;
    }

    public function initialize($id = NULL) {
        $common_key = $this->get_common_key();
        $this->all_fields = array();
        $this->default_fields = array();
        $this->related_fields = array();
        $this->key_fields = array();

        $this->all_fields =& MDI_Model::$mdicommon[$common_key]['_ALL_FIELDS'];
        $this->default_fields =& MDI_Model::$mdicommon[$common_key]['_DEFAULT_FIELDS'];
        $this->related_fields =& MDI_Model::$mdicommon[$common_key]['_RELATED_FIELDS'];
        $this->key_fields =& MDI_Model::$mdicommon[$common_key]['_KEY_FIELDS'];
    }

    protected function post_model_init($nothing) {
        $common_key = $this->get_common_key();

        if (!isset(MDI_Model::$mdicommon[$common_key])) {
            MDI_Model::$mdicommon[$common_key] = array();
        }

        MDI_Model::$mdicommon[$common_key]['_ALL_FIELDS'] = array();
        MDI_Model::$mdicommon[$common_key]['_DEFAULT_FIELDS'] = array();
        MDI_Model::$mdicommon[$common_key]['_RELATED_FIELDS'] = array();
        MDI_Model::$mdicommon[$common_key]['_KEY_FIELDS'] = array();

        foreach ($this->validation as $field => $attribute) {
            MDI_Model::$mdicommon[$common_key][$field] = array();
            $field_attr =& MDI_Model::$mdicommon[$common_key]['_ALL_FIELDS'][$field];
            $field_attr = array();
            $field_attr['label'] = $field;
            $field_attr['admin'] = array();
            $field_attr['rules'] = array();

            // check related field
            if (isset($this->has_one[$field]) || isset($this->has_many[$field])) {

                if (isset($this->has_one[$field])) {
                    $attribute = $this->has_one[$field];
                    $field_attr['type'] = 'has_one';
                }
                else if (isset($this->has_many[$field])) {
                    $attribute = $this->has_many[$field];
                    $field_attr['type'] = 'has_many';
                } else {
                    continue;
                }

                $field_attr = $attribute + $field_attr;
                $dest_fields =& MDI_Model::$mdicommon[$common_key]['_RELATED_FIELDS'];

            // default field
            } else {
                $is_key_field = FALSE;

                // check key fields
                $pos = strrchr($field, '_id');
                if ($pos == '_id') {
                    $related_field = substr($field, 0, strrpos($field, '_id'));

                    if (isset($this->has_one[$related_field]) || isset($this->has_many[$related_field])) {
                        $is_key_field = TRUE;
                    }
                }

                // rules
                foreach ($attribute['rules'] as $key => $value) {
                    if (is_numeric($key)) {
                        $rule_key = $value;
                        $rule_param = NULL;
                    } else {
                        $rule_key = $key;
                        $rule_param = $value;
                    }

                    switch($rule_key) {
                        case 'choices':
                            $choices = $attribute['rules']['choices'];

                            if (is_array($choices)) {
                                // continue
                            } else if (is_string($choices)) {
                                $choices = unserialize($choices);

                                if (is_array($choices)) {
                                    // continue
                                }
                            }

                            $rule_param = $choices;
                            break;
                    }

                    $field_attr['rules'][$rule_key] = $rule_param;
                }

                if ($is_key_field) {
                    $field_attr['type'] = 'key';
                }

                if ($is_key_field) {
                    $dest_fields =& MDI_Model::$mdicommon[$common_key]['_KEY_FIELDS'];
                } else {
                    $dest_fields =& MDI_Model::$mdicommon[$common_key]['_DEFAULT_FIELDS'];
                }

            } // if check related field else end

            // common attribute
            // label
            if (isset($attribute['label'])) {
                $field_attr['label'] = $attribute['label'];
            }

            // admin
            if (isset($attribute['admin'])) {
                $field_attr_admin =& $field_attr['admin'];

                if (!is_array($attribute['admin'])) {
                    $rule_key = $attribute['admin'];
                    $field_attr_admin = array($rule_key => NULL);
                } else {
                    foreach($attribute['admin'] as $key => $value) {
                        if (is_numeric($key)) {
                            $rule_key = $value;
                            $rule_param = NULL;
                        } else {
                            $rule_key = $key;
                            $rule_param = $value;
                        }

                        $field_attr_admin[$rule_key] = $rule_param;
                    }
                }
            }

            $dest_fields[$field] =& $field_attr;
        } // foreach end
    }

    // features
    public function __toString() {
        if (isset($this->id)) {
            return get_class($this).' object(id:'.$this->id.')';
        } else {
            return get_class($this).' object';
        }
    }

    public function label() {
        if (property_exists($this, 'label')) {
            return $this->label;
        } else {
            return strtolower(get_class($this));
        }
    }

    public function get_related_type($field) {
        if (array_key_exists($field, $this->related_fields)) {
            if (isset($this->has_one[$field])) {
                $result = 'ot';
            } else {
                $result = 'mt';
            }

            $related_properties = $this->related_fields[$field];
            $other_class = $related_properties['class'];
            $other_field = $related_properties['other_field'];
            $other_object = new $other_class();

            if (!isset($other_object->related_fields[$other_field])) {
                // middle.comment
                // reversal relationship of field must exist

                return 'none';
            }

            if (isset($other_object->has_one[$other_field])) {
                $result .= 'o';
            } else {
                $result .= 'm';
            }

            return $result;
        }

        return 'none';
    }

    public function first() {
        if ($this->exists()) {
            return $this->all[0];
        }

        return $this;
    }
    public function is_exists_field($field) {
        $common_key = $this->get_common_key();
        return array_key_exists($field, MDI_Model::$mdicommon[$common_key]['_ALL_FIELDS']);
    }

    public function mdi_get_paged(
        $page = 1, $page_size = 50, $paginator_size = 10,
        $page_num_by_rows = FALSE, $info_object = 'paged',
        $iterated = FALSE)
    {
        parent::get_paged($page, $page_size, $page_num_by_rows, $info_object, $iterated);

        $paginator = array();
        $paginator_current_index = (($page-1)%$paginator_size);
        $paginator_begin = $page - $paginator_current_index;
        $total_pages = $this->{$info_object}->total_pages;

        for ($i=0; $i<$paginator_size; ++$i) {
            if (($paginator_begin + $i) > $total_pages) {
                break;
            }

            $attribute = array();

            if ($i == 0) {
                $attribute['begin'] = TRUE;
            }

            if ($i == ($paginator_size - 1)) {
                $attribute['end'] = TRUE;
            }

            if (($paginator_begin + $i) == 1) {
                $attribute['first'] = TRUE;
            }

            if (($paginator_begin + $i) == $total_pages) {
                $attribute['last'] = TRUE;
            }

            if ($i == $paginator_current_index) {
                $attribute['current'] = TRUE;
            }

            $paginator[$paginator_begin + $i] = $attribute;
        }

        $this->{$info_object}->paginator = $paginator;
        return $this;
    }

    public function set_fields($data) {
        if (is_object($data)) {
            if (get_class($data) != get_class($this)) {
                mdi::error(get_class($this).'::set_fields - model do not match('.get_class($data).')');///
            }

            foreach ($data->fields as $field) {
                if (!$this->is_exists_field($field)) {
                    mdi::error(get_class($this).'::set_fields - field does not exist('.$field.')');///
                }

                $this->{$field.'_value_set'}($data->{$field});
            }
        } else if(is_array($data)) {
            foreach ($data as $field => $value) {
                if (!$this->is_exists_field($field)) {
                    mdi::error(get_class($this).'::set_fields - field does not exist('.$field.')');///
                }

                $this->{$field.'_value_set'}($value);
            }
        } else {
            mdi::error(get_class($this).'::set_fields - invalid data type('.gettype($data).')');///
        }
    }

    public function &all_assoc() {
        if ($this->all_array_uses_ids) {
            if(isset($this->_dm_dataset_iterator)) {
                $assoc = array();
                $newid = 0;
                foreach($this->_dm_dataset_iterator as $object) {
                    if (isset($object->id) && $object->id >= 0) {
                        $assoc[$object->id] = $object;
                    } else {
                        $assoc[--$newid] = $object;
                    }
                }
                return $assoc;
            } else {
                return $this->all;
            }
        } else {
            $assoc = array();
            $newid = 0;
            foreach($this->getIterator() as $object) {
                if (isset($object->id) && $object->id >= 0) {
                    $assoc[$object->id] = $object;
                } else {
                    $assoc[--$newid] = $object;
                }
            }

            return $assoc;
        }
    }

    public function &all_ids() {
        $ids = array();
        if(isset($this->_dm_dataset_iterator)) {
            $newid = 0;
            foreach($this->_dm_dataset_iterator as $object) {
                if (isset($object->id) && $object->id >= 0) {
                    $ids[] = $object->id;
                } else {
                    $ids[] = --$newid;
                }
            }
        } else {
            $newid = 0;
            foreach($this->getIterator() as $object) {
                if (isset($object->id) && $object->id >= 0) {
                    $ids[] = $object->id;
                } else {
                    $ids[] = --$newid;
                }
            }
        }

        return $ids;
    }

    public function save($object = '', $related_field = ''){
        $is_update = isset($this->id) ? TRUE : FALSE;

        if (!empty($this->changed_related_values)) {
            // recursive terminate
            $orginal_related_values = array_merge(array(), $this->orginal_related_values);
            $changed_related_values = array_merge(array(), $this->changed_related_values);
            $this->orginal_related_values = array();
            $this->changed_related_values = array();

            $this->_auto_trans_start();
            $delete_related_fields = array();
            $save_related_fields = array();

            foreach($changed_related_values as $field => $changed) {
                foreach($changed as $action => $o_list) {
                    foreach($o_list as $o) {
                        if ($o->force_save) {
                            if (!$o->save()) {
                                $this->error_message($field, $o->error->string);
                                $this->_auto_trans_terminate();
                                return FALSE;
                            }
                        }

                        if ($action == 'save') {
                            $dest_related_fields =& $save_related_fields;
                        } else if($action == 'delete') {
                            $dest_related_fields =& $delete_related_fields;
                        } else {
                            continue;
                        }

                        if (isset($this->has_one[$field])) {
                            $dest_related_fields[$field] = $o;
                        } else {
                            if (!isset($dest_related_fields[$field])) {
                                $dest_related_fields[$field] = array();
                            }

                            $dest_related_fields[$field][] = $o;
                        }
                    }
                }
            }

            if (!empty($delete_related_fields)) {
                if (!parent::delete($delete_related_fields)) {
                    $this->error_message('_delete', $object->error->string);
                    $this->_auto_trans_terminate();
                    return FALSE;
                }
            }

            if (is_array($object)) {
                $save_related_fields = $object + $save_related_fields;
            } else if (!empty($related_field)) {
                $save_related_fields[$related_field] = $object;
            }

            if (parent::save($save_related_fields)) {
                if ($this->_auto_trans_complete('MDI_Model save changed related fields')) {
                    $this->transaction_history = array();
                } else {
                    $this->orginal_related_values = array_merge(array(), $orginal_related_values);
                    $this->changed_related_values = array_merge(array(), $changed_related_values);
                    return FALSE;
                }
            } else {
                $this->orginal_related_values = array_merge(array(), $orginal_related_values);
                $this->changed_related_values = array_merge(array(), $changed_related_values);
                $this->_auto_trans_terminate();
                return FALSE;
            }

        } else {
            if (!parent::save($object, $related_field)) {
                return FALSE;
            }
        }

        // history
        if (mdi::config('admin_using_history')) {
            if ($is_update) {
                $this->_save_history($this->id, 'modify');
            } else {
                $this->_save_history($this->id, 'new');
            }
        }

        return TRUE;
    }

    public function delete($object = '', $related_field = '') {
        $id = isset($this->id) ? $this->id : NULL;

        if (!parent::delete($object, $related_field)) {
            return FALSE;
        }

        if (mdi::config('admin_using_history')) {
            if (empty($object) && ! is_array($object)) {
                if (!empty($id)) {
                    // self delete
                    $this->_save_history($id, 'delete');
                }
            } else {
                // relation delete
                $this->_save_history($id, 'modify');
            }
        }

        return TRUE;
    }

    public function update($field, $value = NULL, $escape_values = TRUE) {
        if (!parent::update($field, $value, $escape_values)) {
            return FALSE;
        }

        if (mdi::config('admin_using_history')) {
            //middle.fixme
        }

        return TRUE;
    }

    public function clear() {
        parent::clear();
        $this->orginal_related_values = array();
        $this->changed_related_values = array();
        $this->transaction_history = array();
    }

    public function get($limit = NULL, $offset = NULL){
        // reset changed relation
        if (!empty($this->parent)) {
            if (array_key_exists('object', $this->parent) && array_key_exists('this_field', $this->parent)) {
                $object = $this->parent['object'];
                $this_field = $this->parent['this_field'];
                if (array_key_exists($this_field, $object->original_related_values)) {
                    unset($object->original_related_values[$this_field]);
                }
                if (array_key_exists($this_field, $object->changed_related_values)) {
                    unset($object->changed_related_values[$this_field]);
                }

            }
        }

        return parent::get($limit, $offset);
    }

    public function get_raw($limit = NULL, $offset = NULL, $handle_related = TRUE) {
        // reset changed relation
        if (!empty($this->parent)) {
            if (array_key_exists('object', $this->parent) && array_key_exists('this_field', $this->parent)) {
                $object = $this->parent['object'];
                $this_field = $this->parent['this_field'];
                if (array_key_exists($this_field, $object->original_related_values)) {
                    unset($object->original_related_values[$this_field]);
                }
                if (array_key_exists($this_field, $object->changed_related_values)) {
                    unset($object->changed_related_values[$this_field]);
                }
            }
        }

        return parent::get_raw($limit, $offset, $handle_related);
    }

    public function as_json($kwargs=array(), $encoding=TRUE) {
        $common_key = $this->get_common_key();

        if (!isset(MDI_Model::$mdicommon[$common_key])) {
            // middle.error
            if ($encoding) {
                return json_encode(array());
            }

            return array();
        }

        $result = array();
        if (isset(MDI_Model::$mdicommon[$common_key]['_VALIDATION'])) {
            foreach (MDI_Model::$mdicommon[$common_key]['_VALIDATION'] as $key => $value) {
                if (is_array($kwargs)) {
                    if (array_key_exists($key, $kwargs)) {
                        if ($kwargs[$key] == FALSE) {
                            continue;
                        }
                    }
                }

                $result[$key] = $this->{$key};
            }
        }

        foreach(array('_HAS_ONE', '_HAS_MANY') as $relation)  {
            if (isset(MDI_Model::$mdicommon[$common_key][$relation])) {
                foreach (MDI_Model::$mdicommon[$common_key][$relation] as $key => $value) {
                    if (is_array($kwargs)) {
                        if (array_key_exists($key, $kwargs)) {
                            if (is_bool($kwargs[$key])) {
                                if ($kwargs[$key] == FALSE) {
                                    continue;
                                }
                            }

                            if ($relation == '_HAS_ONE') {
                                if ($this->{$key}->get()->exists()) {
                                    $result[$key] = $this->{$key}->as_json($kwargs[$key], FALSE);
                                } else {
                                    $result[$key] = NULL;
                                }
                            } else if ($relation == '_HAS_MANY') {
                                if ($this->{$key}->get()->exists()) {
                                    $has_many_result = array();

                                    foreach ($this->{$key} as $o) {
                                        $has_many_result[] = $this->{$key}->as_json($kwargs[$key], FALSE);
                                    }

                                    $result[$key] = $has_many_result;
                                } else {
                                    $result[$key] = array();
                                }
                            }
                        }
                    } else {
                        // explicit field only
                    }

                }
            }
        }

        if ($encoding) {
            return json_encode($result);
        }

        return $result;
    }

    protected function _auto_trans_start(){
        // Begin auto transaction
        if ($this->auto_transaction)
        {
            $this->trans_start();
        }
    }

    protected function _auto_trans_complete($label = 'complete') {
        $this->transaction_history[] = $label;
        $label = implode('<br>', $this->transaction_history);
        $result = TRUE;

        // Complete auto transaction
        if ($this->auto_transaction)
        {
            // Check if successful
            if (!$this->trans_complete())
            {
                $rule = 'transaction';

                // Get corresponding error from language file
                if (FALSE === ($line = $this->lang->line($rule)))
                {
                    $line = 'Unable to access the ' . $rule .' error message.';
                }

                // Add transaction error message
                $this->error_message($rule, sprintf($line, $label));

                // Set validation as failed
                $this->valid = FALSE;

                $result = FALSE;
            }
        }

        if ($this->db->_trans_depth == 0) {
            $this->transaction_history = array();
        }

        return $result;
    }

    protected function _auto_trans_terminate() {
        if ($this->db->_trans_depth > 0) {
            $this->db->_trans_depth = 0;
            $this->db->trans_rollback();
        }
    }

    protected function _save_history($model_id, $history_action) {
        if (!$this->using_history) {
            return;
        }

        $user_model = MDI_USER_MODEL;
        $user = $user_model::get_auth_user();

        $history = new MDI_History();
        $history->model_name = get_class($this);
        $history->model_id = $model_id;
        $history->action = $history_action;

        if ($user) {
            $history->save($user, 'user');
        } else {
            $history->save();
        }
    }

    // abstract features
    public function post_sync() {} // using db syncher

    // db syncher validations
    protected function _int($field, $param=NULL) {}
    protected function _varchar($field, $param) {}
    protected function _text($field) {}
    protected function _null($field, $param) {}
    protected function _auto_increment($field) {}
    protected function _default($field, $param) {}
    protected function _index($field, $param=NULL) {}
    protected function _date($field, $param=NULL) {
        if (empty($this->{$field})) {
            return;
        }

        if ($param) {
            $this->{$field} = date($param, strtotime($this->{$field}));
        } else {
            $this->{$field} = date("Y-m-d", strtotime($this->{$field}));
        }
    }

    protected function _datetime($field, $param=NULL) {
        if (empty($this->{$field})) {
            return;
        }

        if ($param) {
            $this->{$field} = date($param, strtotime($this->{$field}));
        } else {
            $this->{$field} = date("Y-m-d H:i:s", strtotime($this->{$field}));
        }
    }

    protected function _boolean($field) {
        if (!is_numeric($this->{$field}) && is_string($this->{$field})) {
            $this->{$field} = strtoupper($this->{$field}) == 'TRUE' ? TRUE : FALSE;
        } else {
            $this->{$field} = (boolean)$this->{$field};
        }
    }

    // validations
    function _choices($field, $param) {
        if (empty($param)) {
            return "define the value of choices rule(field:$field)";///
        }

        $common_key = $this->get_common_key();
        if (!isset(MDI_Model::$mdicommon[$common_key]['_ALL_FIELDS'][$field]['rules']['choices'])) {
            return "value of choices rule is not a array type(field:$field)";///
        }

        $choices = MDI_Model::$mdicommon[$common_key]['_ALL_FIELDS'][$field]['rules']['choices'];

        if (!array_key_exists($this->{$field}, $choices)) {
            return "value that cannot be found in choices rule (field:$field) (value:".(string)$this->{$field}.")";///
        }

        return TRUE;
    }

    // admin widget
    private function _check_admin_widget_permission($user, $attribute, $default) {
        if ($attribute == NULL) {
            mdi::error(get_class($this).'::_check_admin_widget_permission - attribute is null');///
        }

        if (array_key_exists('hide', $attribute)) {
            return FALSE;
        }

        if (array_key_exists('readonly', $attribute)) {
            if (isset($default) && (is_mdinull($default))) {
                return FALSE;
            }
        }

        return TRUE;
    }

    public function admin_widget_int($user, $attribute, $default) {
        if (!$this->_check_admin_widget_permission($user, $attribute, $default)) {
            return NULL;
        }

        $name = $attribute['name'];
        $value = (string)$default;
        if (array_key_exists('readonly', $attribute)) {
            $format = '<p class="form-control-static">%2$s</p>';
        } else {
            $format = '<input type="number" class="form-control" id="%1$s" name="%1$s" value="%2$s">';
        }

        $widget = sprintf($format, $name, $value);
        return $widget;
    }

    public function admin_widget_varchar($user, $attribute, $default) {
        if (!$this->_check_admin_widget_permission($user, $attribute, $default)) {
            return NULL;
        }

        $name = $attribute['name'];
        $value = (string)$default;

        if (array_key_exists('readonly', $attribute)) {
            $format = '<p class="form-control-static">%2$s</p>';
        } else {
            $format = '<input type="text" class="form-control" id="%1$s" name="%1$s" value="%2$s">';
        }

        $widget = sprintf($format, $name, $value);
        return $widget;
    }

    public function admin_widget_text($user, $attribute, $default) {
        if (!$this->_check_admin_widget_permission($user, $attribute, $default)) {
            return NULL;
        }

        $name = $attribute['name'];
        $value = (string)$default;

        if (array_key_exists('readonly', $attribute)) {
            $format = '<p class="form-control-static">%2$s</p>';
        } else {
            $format = '<textarea class="form-control" id="%1$s" name="%1$s" rows="5">%2$s</textarea>';
        }

        $widget = sprintf($format, $name, $value);
        return $widget;
    }

    public function admin_widget_boolean($user, $attribute, $default) {
        if (!$this->_check_admin_widget_permission($user, $attribute, $default)) {
            return NULL;
        }

        $name = $attribute['name'];
        $value = (string)$default;

        if (!is_numeric($value) && is_string($value)) {
            $value = strtoupper($value) == 'TRUE' ? TRUE : FALSE;
        } else if (is_numeric($value)) {
            $value = (int)$value != 0 ? TRUE : FALSE;
        }

        $checked = $value ? 'checked="checked"' : '';

        if (array_key_exists('readonly', $attribute)) {
            $format = '<input type="checkbox" %2$s disabled>';
        } else {
            $format = '<input type="hidden" name="%1$s" value="false" />';
            $format .= '<input type="checkbox" name="%1$s" value="true" %2$s >';
        }

        $widget = sprintf($format, $name, $checked);
        return $widget;
    }

    public function admin_widget_date($user, $attribute, $default) {
        if (!$this->_check_admin_widget_permission($user, $attribute, $default)) {
            return NULL;
        }

        $name = $attribute['name'];
        $value = (string)$default;
        $data_date_formet = 'data-date-format="YYYY-MM-DD"';
        $data_default_date = NULL;
        $default_date = NULL;

        if ($value) {
            $default_date = date('Y-m-d', strtotime($value));
            $data_default_date = 'data-date-defaultdate="'.$default_date.'"';
        }

        if (array_key_exists('readonly', $attribute)) {
            $widget = '<p class="form-control-static">'.$default_date.'</p>';
        } else {
            $widget = array(
                    '<div class="form-group">',
                    '<div class="input-group date datetimepicker">',
                    '<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>',
                    sprintf('<input type="text" class="form-control" id="%1$s" name="%1$s" data-date-picktime="false" %2$s %3$s/>', $name, $data_date_formet, $data_default_date),
                    '</div>',
                    '</div>',
                );
            $widget = implode('', $widget);
        }

        return $widget;
    }

    public function admin_widget_datetime($user, $attribute, $default) {
        if (!$this->_check_admin_widget_permission($user, $attribute, $default)) {
            return NULL;
        }

        $name = $attribute['name'];
        $value = (string)$default;
        $data_date_formet = 'data-date-format="YYYY-MM-DD HH:mm:ss"';
        $data_default_date = NULL;
        $default_date = NULL;

        if ($value) {
            $default_date = date('Y-m-d H:i:s', strtotime($value));
            $data_default_date = 'data-date-defaultdate="'.$default_date.'"';
        }

        if (array_key_exists('readonly', $attribute)) {
            $widget = '<p class="form-control-static">'.$default_date.'</p>';
        } else {
            $widget = array(
                '<div class="form-group">',
                '<div class="input-group date datetimepicker">',
                '<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>',
                sprintf('<input type="text" class="form-control" id="%1$s" name="%1$s" %2$s %3$s/>', $name, $data_date_formet, $data_default_date),
                '</div>',
                '</div>',
            );

            $widget = implode('', $widget);
        }

        return $widget;
    }

    public function admin_widget_choices($user, $attribute, $default) {
        if (!$this->_check_admin_widget_permission($user, $attribute, $default)) {
            return NULL;
        }

        $name = $attribute['name'];
        $choices = $attribute['choices'];
        $value = (string)$default;

        if (array_key_exists('readonly', $attribute)) {
            $format = '<p class="form-control-static">%2$s</p>';
            $widget = sprintf($format, $name, $value);
        } else {
            $widget = '<select class="form-control" id="'.$name.'" name="'.$name.'">';
            foreach($choices as $choice_value => $label) {
                $selected = ($value == (string)$choice_value) ? 'selected' : '';
                $widget .= '<option ';
                $widget .= 'value="'.$choice_value.'" '.$selected.'>'.$label.'</option>';
            }

            $widget.= '</select>';
        }

        return $widget;
    }

    public function admin_widget_has_key($user, $attribute, $default) {
        return NULL;
    }

    public function admin_widget_has_one($user, $attribute, $default) {
        if (!$this->_check_admin_widget_permission($user, $attribute, $default)) {
            return NULL;
        }

        if (!array_key_exists('model', $attribute)) {
            mdi::error(get_class($this).'::admin_widget_has_one - model that does not exist in attribute');///
        }

        $name = $attribute['name'];
        $model = $attribute['model'];
        $id = (string)$default;
        $label = NULL;
        $url_search = admin_url('popup/search_model');
        $url_link = admin_url('model');

        if ($id) {
            $object = new $model();
            $object->get_by_id($id);
            if ($object->exists()) {
                $label = (string)$object;
            } else {
                $id = NULL;
            }
        }

        if (array_key_exists('readonly', $attribute)) {
            $format = '<p class="form-control-static">%1$s</p>';
            $widget = sprintf($format, $label);
        } else {
            $widget = array(
                sprintf('<div class="form-group mdi-modelselector" data-url-search="%1$s" data-url-link="%2$s" data-model="%3$s" data-name="%4$s">', $url_search, $url_link, $model, $name),
                '<div class="input-group">',
                '<span class="input-group-addon cursor-hand search"><span class="glyphicon glyphicon-search"></span></span>',
                sprintf('<input type="text" class="form-control text" value="%2$s"/>', $name, $label),
                sprintf('<input type="hidden" name="%1$s" class="value" value="%2$s"/>', $name, $id),
                '</div>',
                '</div>',
            );

            $widget = implode('', $widget);
        }

        return $widget;
    }

    public function admin_widget_has_many($user, $attribute, $default) {
        if (!$this->_check_admin_widget_permission($user, $attribute, $default)) {
            return NULL;
        }

        if (!array_key_exists('model', $attribute)) {
            mdi::error(get_class($this).'::admin_widget_has_many - the model that does not exist in attribute');///
        }

        if (!$default || empty($default) || is_mdinull($default)) {
            $default = array();
        } else if (is_array($default)) {
            $default = array_filter($default);

            if (!is_numeric_array($default)) {
                mdi::error(get_class($this).'::admin_widget_has_many - the default value are allow only numeric array');///
            }
        }

        $name = $attribute['name'];
        $model = $attribute['model'];
        $ids = $default;
        $url_search = admin_url('popup/search_model');
        $url_link = admin_url('model');
        $data = array();

        if (!empty($ids)) {
            $object = new $model();
            $object->where_in('id', $ids)->get();

            if ($object->exists()) {
                foreach($object as $o) {
                    $data[] = array(
                        'id' => $o->id,
                        'label' => (string)$o,
                    );
                }
            }
        }

        if (array_key_exists('readonly', $attribute)) {
            if (empty($data)) {
                $widget = '<p class="form-control-static"></p>';
            } else {
                $widget = '';
                foreach($data as $v) {
                    $widget .= sprintf('<p class="form-control-static">%1$s</p>', $v);
                }
            }
        } else {
            $widget = array(
                '<div class="panel panel-info" style="border-width:5px; border-left-width: 15px; margin-bottom: 0;">',
                '<div class="panel-body bg-info" style="padding: 0;">',
                sprintf('<div class="form-group mdi-modelselector-multiple" data-url-search="%1$s" data-url-link="%2$s" data-model="%3$s" data-name="%4$s[]">', $url_search, $url_link, $model, $name));

            if (empty($data)) {
                $widget = array_merge($widget, array(
                    '<div class="input-group">',
                    '<span class="input-group-addon cursor-hand search"><span class="glyphicon glyphicon-search"></span></span>',
                    '<input type="text" class="form-control text" value=""/>',
                    sprintf('<input type="hidden" name="%1$s[]" class="value" value/>', $name),
                    '</div>',
                ));
            } else {
                foreach($data as $v) {
                    $widget = array_merge($widget, array(
                        '<div class="input-group">',
                        '<span class="input-group-addon cursor-hand search"><span class="glyphicon glyphicon-search"></span></span>',
                        sprintf('<input type="text" class="form-control text" value="%1$s"/>', $v['label']),
                        sprintf('<input type="hidden" name="%1$s[]" class="value" value="%2$s"/>', $name, $v['id']),
                        '</div>',
                    ));
                }
            }

            $widget = array_merge($widget, array(
                '</div>',
                '</div>',
                '</div>',
            ));

            $widget = implode('', $widget);
        }

        return $widget;
    }

    public function admin_widget_unknown($user, $attribute, $default) {
        return NULL;
    }

    // magic method
    public function __call($method, $arguments) {
        static $watched_methods = array(
            '_choices_display', '_display',
            '_has_rule', '_has_admin', '_has_many_add', '_has_many_del',
            '_as_admin_widget', '_choices_array',
            '_is_related_changed',
            '_type_get', '_label_get', '_admin_get', '_id_get',
            '_value_set',
        );

        foreach ($watched_methods as $watched_method)
        {
            // See if called method is a watched method
            if (strpos($method, $watched_method) !== FALSE)
            {
                $pieces = explode($watched_method, $method);
                if ( ! empty($pieces[0]) && ! empty($pieces[1]))
                {
                    // Watched method is in the middle
                    return $this->{'_' . trim($watched_method, '_')}($pieces[0], array_merge(array($pieces[1]), $arguments));
                }
                else
                {
                    // Watched method is a prefix or suffix
                    return $this->{'_' . trim($watched_method, '_')}(str_replace($watched_method, '', $method), $arguments);
                }
            }
        }

        return parent::__call($method, $arguments);
    }

    private function _display($field, $arguments) {
        if ($this->_has_rule($field, array('choices'))) {
            echo $this->_choices_display($field, $arguments);
        } else if($this->_has_rule($field, array('boolean'))) {
            $is_true = FALSE;
            if (!is_numeric($this->{$field}) && is_string($this->{$field})) {
                $is_true = strtoupper($this->{$field}) == 'TRUE' ? TRUE : FALSE;
            } else {
                $is_true = (boolean)$this->{$field};
            }

            if ($is_true) {
                echo 'True';
            } else {
                echo 'False';
            }
        } else {
            echo $this->{$field};
        }
    }

    private function _choices_display($field, $arguments) {
        if (!$this->is_exists_field($field)) {
            mdi::error(get_class($this).'::'.$field.'_choices_display - can\'t found the "'.$field.'" field');///
        }

        $common_key = $this->get_common_key();
        $rules = MDI_Model::$mdicommon[$common_key]['_ALL_FIELDS'][$field]['rules'];

        if (!isset($rules['choices'])) {
            mdi::error(get_class($this).'::'.$field.'_choices_display - did not define "choices" rule');///
        }

        if (!isset($rules['choices'][$this->{$field}])) {
            return $this->{$field};
        } else {
            return $rules['choices'][$this->{$field}];
        }
    }

    private function _type_get($field, $arguments) {
        if (!$this->is_exists_field($field)) {
            mdi::error(get_class($this).'::'.$field.'_label - '.$field.' field does not exist');///
        }

        $common_key = $this->get_common_key();
        $field_attr =& MDI_Model::$mdicommon[$common_key]['_ALL_FIELDS'][$field];

        if (isset($field_attr['type'])) {
            return $field_attr['type'];
        }

        $rules = $field_attr['rules'];

        foreach ($rules as $key => $value) {
            switch($key) {
                case 'int':
                case 'boolean':
                case 'varchar':
                case 'text':
                case 'date':
                case 'datetime':
                    $field_attr['type'] = $key;
                    return $key;
            }
        }

        return 'unknown';
    }

    private function _label_get($field, $arguments) {
        if (!$this->is_exists_field($field)) {
            mdi::error(get_class($this).'::'.$field.'_label - '.$field.' field does not exist');///
        }

        $common_key = $this->get_common_key();
        return MDI_Model::$mdicommon[$common_key]['_ALL_FIELDS'][$field]['label'];
    }

    private function _admin_get($field, $arguments) {
        if (!$this->is_exists_field($field)) {
            mdi::error(get_class($this).'::'.$field.'_label - '.$field.' field does not exist');///
        }

        $common_key = $this->get_common_key();
        return MDI_Model::$mdicommon[$common_key]['_ALL_FIELDS'][$field]['admin'];
    }

    private function _rule_get($field, $arguments) {
        if (!$this->is_exists_field($field)) {
            mdi::error(get_class($this).'::'.$field.'_label - '.$field.' field does not exist');///
        }

        $common_key = $this->get_common_key();
        return MDI_Model::$mdicommon[$common_key]['_ALL_FIELDS'][$field]['rules'];
    }

    private function _has_rule($field, $arguments) {
        $rules = $this->_rule_get($field, $arguments);

        if (array_key_exists($arguments[0], $rules)) {
            return TRUE;
        }

        return FALSE;
    }

    private function _has_admin($field, $arguments) {
        $admin = $this->_admin_get($field, $arguments);

        if (array_key_exists($arguments[0], $admin)) {
            return TRUE;
        }

        return FALSE;
    }

    private function _choices_array($field, $arguments) {
        if (!$this->is_exists_field($field)) {
            mdi::error(get_class($this).'::'.$field.'_choices_array - '.$field.' field does not exist');///
        }

        $common_key = $this->get_common_key();
        $rules = MDI_Model::$mdicommon[$common_key]['_ALL_FIELDS'][$field]['rules'];

        if (!isset($rules['choices'])) {
            mdi::error(get_class($this).'::'.$field.'_choices_array - did not define "choices" rule');///
        }

        return $rules['choices'];
    }

    private function _id_get($field, $arguments) {
        $common_key = $this->get_common_key();
        $field_attr =& MDI_Model::$mdicommon[$common_key]['_ALL_FIELDS'][$field];

        if (!isset($field_attr['type'])) {
            $type = $this->{$field.'_type_get'}();
        } else {
            $type = $field_attr['type'];
        }

        $this_field =& $this->{$field};

        if ($type == 'has_one') {
            if (!isset($this_field->id)) {
                $this_field->get();
            }

            if ($this_field->exists()) {
                return  $this_field->id;
            }
        } else if ($type == 'has_many') {
            if (!isset($this_field->id)) {
                $this_field->get();
            }

            if ($this_field->exists()) {
                $result = array();
                foreach ($this_field as $o) {
                    $result[] = $o->id;
                }

                return $result;
            }
        } else {
            mdi::error(get_class($this).'::'.$field.'_id - the field is not a related type');///
        }

        return NULL;
    }

    private function _value_set($field, $arguments) {
        if (!array_key_exists($field, $this->all_fields)) {
            mdi::error(get_class($this).'::'.$field.'_value_set - field does not exist');///
        }

        $value = $this->_trim_value(array_get($arguments[0], NULL));

        if (array_key_exists($field, $this->related_fields)) {
            $has_one = isset($this->has_one[$field]);
            $object = NULL;

            if ($has_one) {
                $this->_has_one_set($field, $value);
            } else {
                $this->_has_many_set($field, $value);
            }
        } else {
            $this->{$field} = $value;
        }
    }

    private function _has_many_add($field, $arguments) {
        $value = $this->_trim_value(array_get($arguments[0], NULL));

        $has_many = isset($this->has_many[$field]);
        if (!$has_many) {
            mdi::error(get_class($this).'::'.$field.'_has_many_add - the field that does not exist in "has_many" type');///
        }

        $model = $this->related_fields[$field]['class'];
        $object =& $this->_value_to_has_many_object($model, $value);
        $save = array();

        $new_assoc =& $object->all_assoc();
        if (array_key_exists($field, $this->changed_related_values)) {
            if (array_key_exists('save', $this->changed_related_values[$field])) {
                $save = $this->changed_related_values[$field]['save'];
            }

            if (array_key_exists('delete', $this->changed_related_values[$field])) {
                $delete_fields =& $this->changed_related_values[$field]['delete'];
            }
        } else {
            $this->changed_related_values[$field] = array();
        }

        foreach($new_assoc as $id => $o) {
            if (isset($delete_fields)) {
                if (array_key_exists($id, $delete_fields)) {
                    unset($delete_fields[$id]);
                }
            }

            $save[$id] = $o;
        }

        $related_properties = $this->has_many[$field];
        $object->parent = array('model' => $related_properties['other_field'], 'id' => $this->id, 'object'=> &$this, 'this_field' => $field);
        $this->{$field} = $object;

        $this->changed_related_values[$field]['save'] = $save;
    }

    private function _has_many_del($field, $arguments) {
        $value = $this->_trim_value(array_get($arguments[0], NULL));

        $has_many = isset($this->has_many[$field]);
        if (!$has_many) {
            mdi::error(get_class($this).'::'.$field.'_has_many_del - the field that does not exist in "has_many" type');///
        }

        $model = $this->related_fields[$field]['class'];
        $object =& $this->_value_to_has_many_object($model, $value);
        $delete = array();

        $new_assoc =& $object->all_assoc();
        if (array_key_exists($field, $this->changed_related_values)) {
            if (array_key_exists('delete', $this->changed_related_values[$field])) {
                $delete = $this->changed_related_values[$field]['delete'];
            }

            if (array_key_exists('save', $this->changed_related_values[$field])) {
                $save_fields =& $this->changed_related_values[$field]['save'];
            }
        } else {
            $this->changed_related_values[$field] = array();
        }

        foreach($new_assoc as $id => $o) {
            if (isset($save_fields)) {
                if (array_key_exists($id, $save_fields)) {
                    unset($save_fields[$id]);
                }
            }

            $delete[$id] = $o;
        }

        $related_properties = $this->has_many[$field];
        $object->parent = array('model' => $related_properties['other_field'], 'id' => $this->id, 'object'=> &$this, 'this_field' => $field);
        $this->{$field} = $object;

        $this->changed_related_values[$field]['delete'] = $delete;
    }

    private function _as_admin_widget($field, $arguments) {
        $user = array_get($arguments[0], NULL);
        $data = array_get($arguments[1], NULL);
        $default = new MDINull();

        // set field attribute
        $common_key = $this->get_common_key();
        $field_attr =& MDI_Model::$mdicommon[$common_key]['_ALL_FIELDS'][$field];

        $type = $this->{$field.'_type_get'}();
        $attribute = $this->{$field.'_admin_get'}();

        // set data
        if ($data) {
            if (is_object($data)) {
                if (get_class($this) != get_class($data)) {
                    mdi::error(get_class($this).'::'.$field.'_as_admin_widget - model types of data is a different('.gettype($data).')');///
                }

                if (array_key_exists($field, $this->related_fields)) {
                    $default = $data->{$field.'_id_get'}();
                } else {
                    $default = $data->{$field};
                }
            } else if(is_array($data)) {
                $default = array_key_exists($field, $data) ? $data[$field] : new MDINull();
            } else {
                mdi::error(get_class($this).'::'.$field.'_as_admin_widget - unknown data type('.gettype($data).')');///
            }
        }

        // set field name
        $attribute['name'] = $field;

        // required check
        if (array_key_exists('required', $field_attr['rules'])) {
            $attribute['required'] = NULL;
        }

        // default check
        if (array_key_exists('default', $field_attr['rules'])) {
            if (is_mdinull($default)) {
                $default = $field_attr['rules']['default'];
            }
        }

        // related check
        if (array_key_exists($field, $this->related_fields)) {
            $attribute['model'] = $field_attr['class'];
        }

        // choices check
        if (array_key_exists('choices', $field_attr['rules'])) {
            $attribute['choices'] = $field_attr['rules']['choices'];
            $type = 'choices';
        }

        // widget check
        if (isset($attribute['widget'])) {
            if (method_exists($this, '_'.$attribute['widget'])) {
                return $this->{'_'.$attribute['widget']}($user, $attribute, $default);
            }
        }

        return $this->{'admin_widget_'.$type}($user, $attribute, $default);
    }

    private function _is_related_changed($field, $arguments) {
        if (array_key_exists($field, $this->changed_related_values)) {
            return TRUE;
        }

        return FALSE;
    }

    // internal function
    private function _has_one_set($field, $value) {
        if (array_key_exists($field, $this->related_fields)) {
            $this->_backup_original_related_values($field);

            $model = $this->related_fields[$field]['class'];
            $related_properties = $this->has_one[$field];
            $object = NULL;
            $action = 'save';

            if (!isset($value) || is_mdinull($value)) {
                if (array_key_exists($field, $this->original_related_values)) {
                    $object = $this->original_related_values[$field];
                    $action = 'delete';
                } else {
                    $object = new $model();
                    $action = 'none';
                }
            } else if (is_object($value)) {
                if ($value->model != $model) {
                    mdi::error(get_class($this).'::_has_one_set'.' - model types of value is a different('.$value->model.')'.'(field:'.$field.')');///
                }

                $object =& $value;

                if (!isset($value->id)) {
                    $object->force_save = TRUE;
                }

            } else if (is_array($value)) {
                mdi::error(get_class($this).'::_has_one_set'.' - "has_one" is not allow array values'.'(field:'.$field.')');///
            } else if (is_numeric($value)) {
                $object = new $model();
                $object->get_by_id($value);

                if (!$object->exists()) {
                    if (array_key_exists($field, $this->original_related_values)) {
                        $action = 'delete';
                    } else {
                        $action = 'none';
                    }
                }
            } else {
                mdi::error(get_class($this).'::_has_one_set'.' - type that is not allowed in related field('.gettype($value).')'.'(field:'.$field.')');///
                return;
            }

            $object->parent = array('model' => $related_properties['other_field'], 'id' => $this->id, 'object'=> &$this, 'this_field' => $field);
            $this->{$field} = $object;

            if ($action == 'none') {
                $this->changed_related_values[$field] = array();
            } else {
                $this->changed_related_values[$field] = array(
                    $action => array(&$this->{$field})
                );
            }
        }
    }

    private function _has_many_set($field, $value) {
        if (array_key_exists($field, $this->related_fields)) {
            $this->_backup_original_related_values($field);

            $model = $this->related_fields[$field]['class'];
            $object =& $this->_value_to_has_many_object($model, $value);
            $save = array();
            $delete = array();

            if (!array_key_exists($field, $this->original_related_values)) {
                $new_assoc =& $object->all_assoc();
                foreach($new_assoc as $id => $o) {
                    $save[$id] = $o;
                }
            } else {
                $original_assoc =& $this->original_related_values[$field]->all_assoc();
                $new_assoc =& $object->all_assoc();

                foreach($new_assoc as $id => $o) {
                    if(!array_key_exists($id, $original_assoc)) {
                        $save[$id] = $o;
                    }
                }

                foreach($original_assoc as $id => $o) {
                    if(!array_key_exists($id, $new_assoc)) {
                        $delete[$id] = $o;
                    }
                }
            }

            $related_properties = $this->has_many[$field];
            $object->parent = array('model' => $related_properties['other_field'], 'id' => $this->id, 'object'=> &$this, 'this_field' => $field);
            $this->{$field} = $object;

            $this->changed_related_values[$field] = array(
                'delete' => $delete,
                'save' => $save
            );
        }
    }

    private function _backup_original_related_values($field) {
        if (array_key_exists($field, $this->related_fields)) {
            if (!array_key_exists($field, $this->original_related_values)) {
                if (isset($this->id) && $this->id >= 0) {
                    $this_class = get_class($this);
                    $new_object = new $this_class();
                    $new_object->get_by_id($this->id);
                    if (!isset($new_object->{$field}->id)) {
                        $new_object->{$field}->get();
                    }

                    if ($new_object->{$field}->exists()) {
                        $this->original_related_values[$field] = $new_object->{$field};
                    }
                }
            }
        }
    }

    private function &_modelarray_to_object($model, $array) {
        $ids = array();
        $news = array();
        $news_key = 0;
        foreach($array as $rel_o) {
            if (!isset($rel_o->model) || $rel_o->model != $model) {
                mdi::error(get_class($this).'::'.'_modelarray_to_object - model type of value is different('.$model.'-'.$rel_o->model.')');///
            }

            if (isset($rel_o->id)) {
                $ids[] = $rel_o->id;
            } else {
                $news[--$news_key] = $rel_o;
                $rel_o->force_save = TRUE;
            }
        }

        $object = new $model();
        $object->where_in('id', $ids)->get();
        $object->all = $object->all + $news;
        return $object;
    }

    private function &_value_to_has_many_object($model, &$value) {
        $object = NULL;

        if (!isset($value) || is_mdinull($value)) {
            $object = new $model();
        } else if (is_object($value)) {
            if ($value->model != $model) {
                mdi::error(get_class($this).'::_value_to_has_many_object'.' - model type of value is different('.$value->model.')');///
            }

            $object =& $this->_modelarray_to_object($model, array($value));
        } else if (is_array($value)) {
            if (empty($value)) {
                $object = new $model();
            }
            else if (is_numeric_array($value)) {
                $object = new $model();
                $object->where_in('id', $value)->get();
            } else {
                $object =& $this->_modelarray_to_object($model, $value);
            }

        } else if (is_numeric($value)) {
            $object = new $model();
            $object->where_in('id', array($value))->get();
        } else {
            mdi::error(get_class($this).'::_value_to_has_many_object'.' - type that is not allowed in related field('.gettype($value).')');///
        }

        return $object;
    }

    protected function _trim_value($value) {
        if (is_object($value)) {
            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if ($v == '') {
                    unset($value[$k]);
                }
            }

            return $value;
        }

        if ($value == '') {
            return NULL;
        }

        if ($value != 0 && empty($value)) {
            return NULL;
        }

        return $value;
    }
}