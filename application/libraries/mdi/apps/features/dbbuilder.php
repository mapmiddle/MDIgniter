<?php



class DBBuilder {
    static $CI = NULL;

    public function __construct() {
        $this->getCI()->load->database();
        $this->getCI()->load->dbforge();
    }

    public function make_group_querydict($label) {
        $querydict = array(
            $label => array(
                '_TYPE' => 'GROUP',
                '_LABEL' => $label,
                '_CHILDREN' => array(),
            ),
        );

        return $querydict;
    }

    public function make_table_querydict($label, $action, $attribute=NULL) {
        $querydict = array(
            array(
                '_TYPE' => 'TABLE',
                '_LABEL' => $label,
                '_ACTION' => strtoupper($action),
                '_ATTRIBUTE' => $attribute,
            ),
        );

        return $querydict;
    }

    public function make_field_querydict($table, $label, $action, $attribute) {
        $querydict = array(
            array(
                '_TYPE' => 'FIELD',
                '_TABLE' => $table,
                '_LABEL' => $label,
                '_ACTION' => strtoupper($action),
                '_ATTRIBUTE' => $attribute,
            ),
        );

        return $querydict;
    }

    public function &get_group(&$db_querydict, $labelGroup) {
        if (array_key_exists($labelGroup, $db_querydict)) {
            return $db_querydict[$labelGroup]['_CHILDREN'];
        }

        mdi::error('DBBuilder::get_group Error - '.$labelGroup.'group does not exist in '.$db_querydict);///
        return array();
    }

    public function run($db_querydict) {
        $result = array();
        $this->_run($db_querydict, $result);

        foreach($result as $sql) {
            if (is_string($sql)) {
                self::getCI()->db->query($sql);
            } else if (is_array($sql)) {
                if (array_key_exists('_METHOD', $sql)) {
                    if (array_key_exists('_ARGS', $sql)) {
                        call_user_func_array(array($this->getCI()->dbforge, $sql['_METHOD']), $sql['_ARGS']);
                    } else {
                        call_user_func(array($this->getCI()->dbforge, $sql['_METHOD']));
                    }
                }
            }
        }
    }

    protected function _run($db_querydict, &$result) {
        if (!is_array($db_querydict)) {
            mdi::error('DBBuilder::run Error - is not array type');///
        }

        foreach($db_querydict as $key => $value) {
            if (!array_key_exists('_TYPE', $value)) {
                mdi::error('DBBuilder::run Error - "_TYPE" key does not exist');///
            }

            switch ($value['_TYPE']) {
                case 'GROUP':
                    $this->_run($value['_CHILDREN'], $result);
                    break;
                case 'TABLE':
                    $this->_run_table($value, $result);
                    break;
                case 'FIELD':
                    $this->_run_field($value, $result);
                    break;
            }
        }
    }

    protected function _run_table($db_querydict, &$result) {
        if (!array_key_exists('_TYPE', $db_querydict)) {
            mdi::error('DBBuilder::_run_table Error - "_TYPE" key does not exist');///
        } else if (!array_key_exists('_LABEL', $db_querydict)) {
            mdi::error('DBBuilder::_run_table Error - "_LABEL" key does not exist');///
        } else if (!array_key_exists('_ACTION', $db_querydict)) {
            mdi::error('DBBuilder::_run_table Error - "_ACTION" key does not exist');///
        } else if ($db_querydict['_TYPE'] !== 'TABLE') {
            mdi::error('DBBuilder::_run_table Error - is not "TABLE" type');///
        }

        $label = $db_querydict['_LABEL'];
        $attribute = array_key_exists('_ATTRIBUTE', $db_querydict) ? $db_querydict['_ATTRIBUTE'] : NULL;

        switch ($db_querydict['_ACTION']) {
            case 'CREATE':
                $result[] = array(
                    '_METHOD' => 'add_field',
                    '_ARGS' => array(
                        array(
                            'id' =>  array(
                                        'type' => 'INT',
                                        'unsigned' => TRUE,
                                        'auto_increment' => TRUE
                                    )
                        )
                    )
                );

                $result[] = array(
                    '_METHOD' => 'add_key',
                    '_ARGS' => array('id', TRUE)
                );

                $method = "create_table";
                $args = array($label);

                if ($attribute) {
                    if(is_array($attribute)) {
                        if (array_key_exists('_IF_NOT_EXISTS', $attribute)) {
                            if ($attribute['_IF_NOT_EXISTS']) {
                                $args[] = TRUE;
                            }
                        }
                    }
                }

                $result[] = array(
                    '_METHOD' => $method,
                    '_ARGS' => $args
                );

                break;
            case 'DROP':
                $result[] = array(
                    '_METHOD' => 'drop_table',
                    '_ARGS' => array($label)
                );

                break;
            default:
                mdi::error('DBBuilder::_run_table Error - invalid "ACTION" ('.$db_querydict['_ACTION'].')');///
        }
    }

    protected function _run_field($db_querydict, &$result) {
        if (!array_key_exists('_TYPE', $db_querydict)) {
            mdi::error('DBBuilder::_run_field Error - "_TYPE" key does not exist');///
        } else if (!array_key_exists('_LABEL', $db_querydict)) {
            mdi::error('DBBuilder::_run_field Error - "_LABEL" key does not exist');///
        } else if (!array_key_exists('_TABLE', $db_querydict)) {
            mdi::error('DBBuilder::_run_field Error - "_TABLE" key does not exist');///
        } else if (!array_key_exists('_ACTION', $db_querydict)) {
            mdi::error('DBBuilder::_run_field Error - "_ACTION" key does not exist');///
        } else if (!array_key_exists('_ATTRIBUTE', $db_querydict)) {
            mdi::error('DBBuilder::_run_field Error - "_ATTRIBUTE" key does not exist');///
        } else if ($db_querydict['_TYPE'] !== 'FIELD') {
            mdi::error('DBBuilder::_run_field Error - is not "FIELD" type');///
        } else if ($db_querydict['_LABEL'] == NULL || empty($db_querydict['_LABEL'])) {
            mdi::error('DBBuilder::_run_field Error - "_LABEL" value does not exist');///
        } else if ($db_querydict['_TABLE'] == NULL || empty($db_querydict['_TABLE'])) {
            mdi::error('DBBuilder::_run_field Error - "_TABLE" value does not exist'.'(field:'.$db_querydict['_LABEL'].')');///
        }

        $table = $db_querydict['_TABLE'];
        $label = $db_querydict['_LABEL'];
        $method = NULL;

        // validation check
        switch ($db_querydict['_ACTION']) {
            case 'ADD':
                break;

            default:
                mdi::error('DBBuilder::_run_field Error - invalid "_ACTION" ('.$db_querydict['_ACTION'].')'."(field:".$db_querydict['_LABEL'].")"."(table:".$db_querydict['_TABLE'].")");///
                break;
        }

        $fields = NULL;

        if (!is_array($db_querydict['_ATTRIBUTE'])) {
            mdi::error('DBBuilder::_run_field Error - "_ATTRIBUTE" is not array type'."(field:".$db_querydict['_LABEL'].")"."(table:".$db_querydict['_TABLE'].")");///
        } else {
            $option = $db_querydict['_ATTRIBUTE']['_OPTION'];
            switch($option) {
                case 'COLUMN':
                    $method = 'add_column';
                    $field = $this->_field_option_column($table, $label, $db_querydict['_ATTRIBUTE']);
                    $fields = array(
                        $label => $field
                    );
                    break;
                case 'INDEX':
                    $method = 'add_index';
                    $field = $this->_field_option_index($table, $label, $db_querydict['_ATTRIBUTE']);
                    $fields = array(
                        $label => $field
                    );
                    break;
                case 'UNIQUE':
                case 'UNIQUE_TOGETHER':
                    $method = 'add_unique';
                    $field = $this->_field_option_unique($table, $label, $db_querydict['_ATTRIBUTE']);
                    $fields = array(
                        $field
                    );
                    break;
                default:
                    mdi::error('DBBuilder::_run_field Error - invalid "OPTION" ('.$option.')'."(field:".$db_querydict['_LABEL'].")"."(table:".$db_querydict['_TABLE'].")");///
            }
        }

        $result[] = array(
            '_METHOD' => $method,
            '_ARGS' => array($table, $fields)
        );
    }

    protected function _field_option_column($table, $label, $attribute) {
        if (!array_key_exists('_DATATYPE', $attribute)) {
            mdi::error('DBBuilder::_field_option_column Error - "_DATATYPE" key does not exist'."(field:".$label.")"."(table:".$table.")");///
        }

        $field = array();
        $datatype = strtoupper($attribute['_DATATYPE']);

        switch($datatype) {
            case 'KEY':
                $field['type'] = 'INT';
                $field['unsigned'] = TRUE;
                break;

            case 'BOOLEAN':
            case 'SMALLINT':
            case 'BIGINT':
            case 'TINYINT':
            case 'INT':
                if ($datatype == 'BOOLEAN') {
                    $field['type'] = 'INT';
                    $field['constraint'] = 1;
                    $field['unsigned'] = TRUE;
                } else {
                    $field['type'] = $attribute['_DATATYPE'];

                    if ($attribute['_DATATYPE'] == 'INT') {
                        if (array_key_exists('_DATASIZE', $attribute) && !empty($attribute['_DATASIZE'])) {
                            $field['constraint'] = $attribute['_DATASIZE'];
                        }
                    }

                    if (array_key_exists('_UNSIGNED', $attribute)) {
                        if ($attribute['_UNSIGNED']) {
                            $field['unsigned'] = TRUE;
                        }
                    }
                }
                break;

            case 'CHAR':
            case 'VARCHAR':
                if (!array_key_exists('_DATASIZE', $attribute) || empty($attribute['_DATASIZE'])) {
                    mdi::error('DBBuilder::_field_option_column Error - "_DATASIZE" key does not exist'."(field:".$label.")"."(table:".$table.")");///
                }

                $field['type'] = $attribute['_DATATYPE'];
                $field['constraint'] = $attribute['_DATASIZE'];
                break;

            case 'TEXT':
            case 'DATE':
            case 'DATETIME':
                if (array_key_exists('_DATASIZE', $attribute) && !empty($attribute['_DATASIZE'])) {
                    mdi::error('DBBuilder::_field_option_column Error - "_DATASIZE" key is deprecated'."(field:".$label.")"."(table:".$table.")");///
                }

                $field['type'] = $attribute['_DATATYPE'];
                break;

            default:
                mdi::error("DBBuilder::_field_option_column Error - unknown data type"."(".$datatype.")"."(field:".$label.")"."(table:".$table.")");///
        }

        if (array_key_exists('_DEFAULT', $attribute)) {
            if (array_key_exists('_AUTO_INCREMENT', $attribute)) {
                mdi::error('DBBuilder::_field_option_column Error - You can not use the "DEFAULT" with  "AUTO_INCREMENT" attribute at the same time'."(".$datatype.")"."(field:".$label.")"."(table:".$table.")");///
            }

            $field['default'] = NULL;

            if (!is_null($attribute['_DEFAULT'])) {
                $default = $attribute['_DEFAULT'];
                switch ($datatype) {
                    case 'TINYINT':
                    case 'BOOLEAN':
                        $default = $default ? 1 : 0;
                        break;
                }

                $field['default'] = $default;
            }
        }

        if (array_key_exists('_AUTO_INCREMENT', $attribute)) {
            if ($attribute['_AUTO_INCREMENT']) {
                $field['auto_increment'] = TRUE;
            }
        }

        $field['null'] = TRUE;
        if (array_key_exists('_NULL', $attribute)) {
            if (!$attribute['_NULL']) {
                $field['null'] = FALSE;
            }
        }

        return $field;
    }

    protected function _field_option_unique($table, $label, $attribute) {
        $field = array();
        $field['field'] = $label;

        if (!array_key_exists('_UNIQUE_TABLE', $attribute) || empty($attribute['_UNIQUE_TABLE'])) {
            $field['unique_table'] = $table.'_'.$label.'_unique';
        } else {
            $field['unique_table'] = $attribute['_UNIQUE_TABLE'];
        }

        return $field;
    }

    protected function _field_option_unique_together($table, $label, $attribute) {
        $field = array();
        $field['field'] = $label;

        if (!array_key_exists('_UNIQUE_TABLE', $attribute) || empty($attribute['_UNIQUE_TABLE'])) {
            $field['unique_table'] = $table.'_'.implode('_', $label).'_unique';
        } else {
            $field['unique_table'] = $attribute['_UNIQUE_TABLE'];
        }

        return $field;
    }

    protected function _field_option_index($table, $label, $attribute) {
        $field = array();

        if (!array_key_exists('_INDEX_TABLE', $attribute) || empty($attribute['_INDEX_TABLE'])) {
            $field['index_table'] = $table.'_'.$label.'_index';
        } else {
            $field['index_table'] = $attribute['_INDEX_TABLE'];
        }

        return $field;
    }

    public function drop_table($tableName) {
        return $this->getCI()->dbforge->drop_table($tableName);
    }

    public function is_table_exists($tableName) {
        return $this->getCI()->dbforge->is_table_exists($tableName);
    }

    public function is_field_exists($tableName, $fieldName) {
        return $this->getCI()->dbforge->is_field_exists($tableName, $fieldName);
    }

    public static function &getCI() {
        if (is_null(self::$CI)) {
            self::$CI =& get_instance();
        }

        return self::$CI;
    }
}