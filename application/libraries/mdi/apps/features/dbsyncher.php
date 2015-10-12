<?php


class DBSyncher {
    static $CI = NULL;

    public function __construct() {
        self::getCI()->load->database();
        self::getCI()->load->dbforge();
        self::getCI()->mdi->load('features/dbbuilder');
    }

    public function drop($model) {
        if (property_exists($model, 'table')) {
            if (is_string($model)) {
                $object = new $model();
                $table = $object->table;
            } else {
                $table = $model->table;
            }
        } else {
            return;
        }

        self::getCI()->dbforge->drop_table($table);
    }

    public function drop_all() {
        //middle.fixme
    }

    public function sync($model) {
        if (is_string($model)) {
            $object = new $model(NULL, TRUE);
        } else if (is_object($model)) {
            $object = $model;
        } else {
            mdi::error('unknown type('.gettype($model).')');///
            return FALSE;
        }

        $objectName = get_class($object);

        if (!property_exists($object, 'table')) {
            $object->table = $this->get_table_name($object);
        }

        if (!property_exists($object, 'validation')) {
            mdi::error($objectName.'"validation" variable does not exist in model');///
            return FALSE;
        }

        $dbbuilder = self::getCI()->mdi->dbbuilder;

        if (!$dbbuilder->is_table_exists($object->table)) {
            $db_querydict = array();
            $db_querydict = array_merge($db_querydict, $dbbuilder->make_group_querydict('PRE'));
            $db_querydict = array_merge($db_querydict, $dbbuilder->make_group_querydict('POST'));
            $db_querydict = array_merge($db_querydict, $dbbuilder->make_group_querydict('RELATED'));

            // create table
            $querydict_group_pre =& $dbbuilder->get_group($db_querydict, 'PRE');
            $new_querydict = $dbbuilder->make_table_querydict($object->table, 'CREATE', array('_IF_NOT_EXISTS' => TRUE));
            $querydict_group_pre = array_merge($querydict_group_pre, $new_querydict);

            // validation setting
            $this->_MDIValidation($object, $db_querydict);

            // relation setting
            $this->_MDIRelation($object, $db_querydict);

            // alter fields setting
            //if(!$this->_MDIOrdering($object, $db_alter_fields)) {
            //    return FALSE;
            //}

            // run dbbuilder
            $dbbuilder->run($db_querydict);

            return TRUE;
        }

        return FALSE;
    }

    public function get_table_name($object) {
        if (!property_exists($object, 'table')) {
            return strtolower(plural(get_class($this)));
        }

        return $object->table;
    }

    protected function _MDIValidation($object, &$outQueryDict) {
        $dbbuilder = self::getCI()->mdi->dbbuilder;
        $querydict_group_pre =& $dbbuilder->get_group($outQueryDict, 'PRE');
        $querydict_group_post =& $dbbuilder->get_group($outQueryDict, 'POST');
        $table_name = $object->table;
        $unique_together_assoc = array_flip($object->unique_together);

        foreach($object->validation as $fieldName => $attributes) {
            // middle.important
            // skip id
            if ($fieldName == 'id') {
                continue;
            }

            $dbbuilder_field_attr = array(
                '_OPTION' => 'COLUMN',
            );

            foreach($attributes['rules'] as $key => $value) {
                $rule_name = NULL;
                $rule_param = NULL;

                if (is_numeric($key)) {
                    $rule_name = $value;
                } else {
                    $rule_name = $key;
                    $rule_param = $value;
                }

                if ($rule_name == 'int') {
                    $dbbuilder_field_attr['_DATATYPE'] = 'INT';
                    $dbbuilder_field_attr['_DATASIZE'] = $rule_param;
                } else if ($rule_name == 'varchar') {
                    $dbbuilder_field_attr['_DATATYPE'] = 'VARCHAR';
                    $dbbuilder_field_attr['_DATASIZE'] = $rule_param;
                } else if ($rule_name == 'text') {
                    $dbbuilder_field_attr['_DATATYPE'] = 'TEXT';
                    $dbbuilder_field_attr['_DATASIZE'] = NULL;
                } else if ($rule_name == 'date') {
                    $dbbuilder_field_attr['_DATATYPE'] = 'DATE';
                    $dbbuilder_field_attr['_DATASIZE'] = NULL;
                } else if ($rule_name == 'datetime') {
                    $dbbuilder_field_attr['_DATATYPE'] = 'DATETIME';
                    $dbbuilder_field_attr['_DATASIZE'] = NULL;
                } else if ($rule_name == 'boolean') {
                    $dbbuilder_field_attr['_DATATYPE'] = 'BOOLEAN';
                } else if ($rule_name == 'null') {
                    $dbbuilder_field_attr['_NULL'] = $rule_param;
                } else if ($rule_name == 'auto_increment') {
                    $dbbuilder_field_attr['_AUTO_INCREMENT'] = TRUE;
                } else if ($rule_name == 'default') {
                    $dbbuilder_field_attr['_DEFAULT'] = $rule_param;
                } else if($rule_name == 'required') {
                    $dbbuilder_field_attr['_NULL'] = FALSE;
                } else if ($rule_name == 'unique') {
                    if (array_key_exists($fieldName, $unique_together_assoc)) {
                        continue;
                    }

                    $dbbuilder_field_unique_attr =  array(
                        '_OPTION' => 'UNIQUE'
                    );

                    $new_querydict = $dbbuilder->make_field_querydict(
                        $table_name, $fieldName, 'ADD', $dbbuilder_field_unique_attr);
                    $querydict_group_post = array_merge($querydict_group_post, $new_querydict);

                } else if ($rule_name == 'index') {
                    $dbbuilder_field_unique_index =  array(
                        '_OPTION' => 'INDEX'
                    );

                    if ($rule_param != NULL) {
                        $dbbuilder_field_unique_index['_INDEX_TABLE'] = $rule_param;
                    }

                    $new_querydict = $dbbuilder->make_field_querydict(
                        $table_name, $fieldName, 'ADD', $dbbuilder_field_unique_index);
                    $querydict_group_post = array_merge($querydict_group_post, $new_querydict);
                }

            } // $attributes['rules'] foreach end

            $new_querydict = $dbbuilder->make_field_querydict(
                $table_name, $fieldName, 'ADD', $dbbuilder_field_attr);
            $querydict_group_pre = array_merge($querydict_group_pre, $new_querydict);
        } // $object->attribute foreach end

        // unique together attribute
        if (!empty($object->unique_together)) {
            $dbbuilder_field_unique_together_attr =  array(
                '_OPTION' => 'UNIQUE_TOGETHER',
            );

            $new_querydict = $dbbuilder->make_field_querydict(
                $table_name, $object->unique_together, 'ADD', $dbbuilder_field_unique_together_attr);
            $querydict_group_post = array_merge($querydict_group_post, $new_querydict);
        }
    }

    protected function _MDIRelation($object, &$outQueryDict) {
        $dbbuilder = self::getCI()->mdi->dbbuilder;
        $querydict_group_rel =& $dbbuilder->get_group($outQueryDict, 'RELATED');

        $this_object = $object;
        $this_model = strtolower(get_class($this_object));
        $this_table = $this_object->table;

        foreach(array('has_one', 'has_many') as $arr) {
            foreach ($this_object->{$arr} as $this_field => $this_field_attr) {
                $other_class = NULL;
                $other_field = NULL;
                $join_table = NULL;

                if (is_int($this_field)) {
                    $this_field = $this_field_attr;
                    $other_class = $this_field_attr;
                    $other_field = $this_model;
                    $this_field_attr = array(
                        'class' => $other_class,
                        'other_field' => $this_model,
                        'related_type' => $arr,
                    );
                } else {
                    if (!empty($this_field_attr['class'])) {
                        $other_class = $this_field_attr['class'];
                    } else {
                        mdi::error("DBSyncher::_MDIRelation Error - class did not defined"."(field:".$this_field.")"."(table:".$this_table.")");///
                    }

                    if (!empty($this_field_attr['other_field'])) {
                        $other_field = $this_field_attr['other_field'];
                    } else {
                        $other_field = $this_model;
                    }

                    if (!empty($this_field_attr['join_table'])) {
                        $join_table = $this_field_attr['join_table'];
                    }

                    //middle.fixme
                    /*
                    if (!empty($this_field_attr['model_path'])) {
                        $other_class_path = $this_field_attr['model_path'].'/'.$other_class;
                    } else {
                        $other_class_path = strtolower($other_class);
                    }
                    */

                    $this_field_attr['related_type'] = $arr;
                }

                // other class model
                $other_object = new $other_class(NULL, TRUE);
                $other_object_related_fields = array();
                $other_table = $other_object->table;

                DBSyncher::_getMDIRelationFields($other_object, $other_object_related_fields);

                if (!array_key_exists($other_field, $other_object_related_fields)) {
                    mdi::error("DBSyncher::_MDIRelation Error - reverse relationship of field must exist at ".$other_class." model"."(field:".$this_field.")");
                } else {
                    $other_field_attr = $other_object_related_fields[$other_field];
                }

                if (empty($other_field_attr['class']) || $other_field_attr['class'] !== $this_model) {
                    mdi::error("DBSyncher::_MDIRelation Error - did not matched class of ".$other_field." field in ".$other_class." model "."(field:".$this_field.")"."(table:".$this_table.")");///
                }

                if (!empty($other_field_attr['other_field']) && $other_field_attr['other_field'] !== $this_field) {
                    mdi::error("DBSyncher::_MDIRelation Error - current field did not matched 'other_field' option of ".$other_field." field in ".$other_class." model"."(field:".$this_field.")"."(table:".$this_table.")");///
                }

                if (!empty($other_field_attr['join_table'])) {
                    if ($join_table == NULL) {
                        mdi::error("DBSyncher::_MDIRelation Error - 'join_table' field did not matched ".$other_field." field in ".$other_class." model"."(field:".$this_field.")"."(table:".$this_table.")");///
                    }
                } else {
                    if ($join_table != NULL) {
                        mdi::error("DBSyncher::_MDIRelation Error - 'join_table' field did not matched ".$other_field." field in ".$other_class." model"."(field:".$this_field.")"."(table:".$this_table.")");/////
                    }
                }

                $join_table = $this->_getMDIJoinTable($this_object, $other_object, $this_field_attr);

                if ($this_model <= $other_class) {
                    $target_object = $object;
                } else {
                    $target_object = $other_object;
                }

                if (!empty($this_field_attr['join_self_as'])) {
                    $this_db_field_name = $this_field_attr['join_self_as'];
                } else {
                    $this_db_field_name = $other_field;
                }

                if (!empty($other_field_attr['join_self_as'])) {
                    $other_db_field_name = $other_field_attr['join_self_as'];
                } else {
                    $other_db_field_name = $this_field;
                }

                if ($arr == 'has_one') {
                    if ($other_field_attr['related_type'] == 'has_one') {
                        // oto
                        ///////////////////////////////////////////////////////////
                        if($target_object === $object){
                            $new_querydict = $dbbuilder->make_field_querydict(
                                $this_table, $other_db_field_name.'_id', 'ADD',
                                array(
                                    '_OPTION' => 'COLUMN',
                                    '_DATATYPE' => 'KEY'
                                )
                            );

                            $querydict_group_rel = array_merge($querydict_group_rel, $new_querydict);
                        } else if ($dbbuilder->is_table_exists($target_object->table)) {
                            if (!$dbbuilder->is_field_exists($other_table, $this_db_field_name.'_id')) {
                                $new_querydict = $dbbuilder->make_field_querydict(
                                    $other_table, $this_db_field_name.'_id', 'ADD',
                                    array(
                                        '_OPTION' => 'COLUMN',
                                        '_DATATYPE' => 'KEY'
                                    )
                                );
                                $querydict_group_rel = array_merge($querydict_group_rel, $new_querydict);
                            }
                        } else {
                            // sync of other class will generate relation ID, instead of
                        }
                    } else {
                        // otm
                        ///////////////////////////////////////////////////////////
                        $new_querydict = $dbbuilder->make_field_querydict(
                            $this_table, $other_db_field_name.'_id', 'ADD',
                            array(
                                '_OPTION' => 'COLUMN',
                                '_DATATYPE' => 'KEY'
                            )
                        );

                        $querydict_group_rel = array_merge($querydict_group_rel, $new_querydict);
                    }
                } else {
                    if ($other_field_attr['related_type'] == 'has_one') {
                        // m2o
                        ///////////////////////////////////////////////////////////
                        if ($dbbuilder->is_table_exists($other_table)) {
                            if (!$dbbuilder->is_field_exists($other_table, $this_db_field_name.'_id')) {
                                $new_querydict = $dbbuilder->make_field_querydict(
                                    $other_table, $this_db_field_name.'_id', 'ADD',
                                    array(
                                        '_OPTION' => 'COLUMN',
                                        '_DATATYPE' => 'KEY',
                                    )
                                );

                                $querydict_group_rel = array_merge($querydict_group_rel, $new_querydict);
                            }
                        } else {
                            // sync of other class will generate relation ID, instead of
                        }
                    } else {
                        // m2m
                        ///////////////////////////////////////////////////////////

                        if ($dbbuilder->is_table_exists($join_table)) {
                            if (!$dbbuilder->is_field_exists($join_table, $this_db_field_name.'_id')) {
                                // add field
                                $new_querydict = $dbbuilder->make_field_querydict(
                                    $join_table, $this_db_field_name.'_id', 'ADD',
                                    array(
                                        '_OPTION' => 'COLUMN',
                                        '_DATATYPE' => 'KEY',
                                    )
                                );

                                $querydict_group_rel = array_merge($querydict_group_rel, $new_querydict);
                            }

                            if (!$dbbuilder->is_field_exists($join_table, $other_db_field_name.'_id')) {
                                $new_querydict = $dbbuilder->make_field_querydict(
                                    $join_table, $other_db_field_name.'_id', 'ADD',
                                    array(
                                        '_OPTION' => 'COLUMN',
                                        '_DATATYPE' => 'KEY',
                                    )
                                );

                                $querydict_group_rel = array_merge($querydict_group_rel, $new_querydict);
                            }

                        } else {
                            // create table
                            $new_querydict = $dbbuilder->make_table_querydict($join_table, 'CREATE', array('_IF_NOT_EXISTS' => TRUE));
                            $querydict_group_rel = array_merge($querydict_group_rel, $new_querydict);

                            // add field
                            $new_querydict = $dbbuilder->make_field_querydict(
                                $join_table, $this_db_field_name.'_id', 'ADD',
                                array(
                                    '_OPTION' => 'COLUMN',
                                    '_DATATYPE' => 'KEY',
                                )
                            );

                            $querydict_group_rel = array_merge($querydict_group_rel, $new_querydict);

                            $new_querydict = $dbbuilder->make_field_querydict(
                                $join_table, $other_db_field_name.'_id', 'ADD',
                                array(
                                    '_OPTION' => 'COLUMN',
                                    '_DATATYPE' => 'KEY',
                                )
                            );

                            $querydict_group_rel = array_merge($querydict_group_rel, $new_querydict);
                        }
                    }
                }

            }
        }

        return TRUE;
    }

    protected function _getMDIRelationFields($object, &$outRelatedField) {
        $outRelatedField = array();

        foreach(array('has_one', 'has_many') as $arr)
        {
            foreach ($object->{$arr} as $name => $attributes)
            {
                if (is_int($name)) {
                    $outRelatedField[$attributes] = array(
                        'class' => $attributes,
                        'other_field' => strtolower(get_class($object)),
                        'related_type' => $arr,
                    );
                } else {
                    $attributes['related_type'] = $arr;
                    $outRelatedField[$name] = $attributes;
                }
            }
        }
    }

    protected function _getMDIJoinTable($this_object, $other_object, $this_field_attr)
    {
        $other_prefix = $other_object->prefix;
        $other_table = $other_object->table;

        // was a join table defined for this relation?
        if ( ! empty($this_field_attr['join_table']) ) {
            $relationship_table = $this_field_attr['join_table'];
        } else {
            // Check if self referencing
            if ($this_object->table == $other_table) {
                // use the model names from related_properties
                $p_this_model = plural(strtolower(get_class($this_object)));
                $p_other_model = plural(strtolower(get_class($other_object)));
                $relationship_table = ($p_this_model < $p_other_model) ? $p_this_model . '_' . $p_other_model : $p_other_model . '_' . $p_this_model;
            }
            else {
                $relationship_table = ($this_object->table < $other_table) ? $this_object->table . '_' . $other_table : $other_table . '_' . $this_object->table;
            }

            // Remove all occurances of the prefix from the relationship table
            $relationship_table = str_replace($other_prefix, '', str_replace($this_object->prefix, '', $relationship_table));

            // So we can prefix the beginning, using the join prefix instead, if it is set
            $relationship_table = (empty($this_object->join_prefix)) ? $this_object->prefix . $relationship_table : $this_object->join_prefix . $relationship_table;
        }

        return $relationship_table;
    }

    public static function &getCI() {
        if (is_null(self::$CI)) {
            self::$CI =& get_instance();
        }

        return self::$CI;
    }
};