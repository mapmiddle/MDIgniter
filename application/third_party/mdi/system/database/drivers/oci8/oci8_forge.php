<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MDI_DB_oci8_forge extends CI_DB_oci8_forge {

    /**
     * Is Table Exists
     *
     * @access	public
     * @param	string	the table name
     * @return	bool
     */
    public function is_table_exists($table) {
        $query = $this->db->query(
            "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ".
            $this->db->escape($table)
        )->result();
        return !empty($query);
    }

    /**
     * Is Field Exists
     *
     * @access	public
     * @param	string	the table name
     * @return	bool
     */
    public function is_field_exists($table, $field) {
        $query = $this->db->query(
            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ".
            $this->db->escape($table).
            " AND COLUMN_NAME = ".
            $this->db->escape($field)
        )->result();
        return !empty($query);
    }

    /**
     * Index Add
     *
     * @access	public
     * @param	string	the table name
     * @return	bool
     */
    function add_index($table = '', $field = array())
    {
        if ($table == '')
        {
            show_error('A table name is required for that operation.');
        }

        foreach ($field as $k => $v)
        {
            if (!is_array($v) || !array_key_exists('index_table', $v))
            {
                continue;
            }

            $index_table = $v['index_table'];
            $sql = "CREATE INDEX ".
                $this->db->_protect_identifiers($index_table).
                " ON ".$this->db->_protect_identifiers($table).
                "(".$this->db->_protect_identifiers($k).")";

            if ($this->db->query($sql) === FALSE)
            {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Unique Add
     *
     * @access	public
     * @param	string	the table name
     * @return	bool
     */
    function add_unique($table = '', $field = array())
    {
        if ($table == '')
        {
            show_error('A table name is required for that operation.');
        }

        foreach ($field as $v)
        {
            if (!is_array($v) || !array_key_exists('unique_table', $v) || !array_key_exists('field', $v))
            {
                continue;
            }

            $field = $v['field'];
            $unique_table = $v['unique_table'];

            if (is_array($field))
            {
                $fields = '';
                foreach ($field as $name)
                {
                    $fields .= "`$name`,";
                }

                $fields = trim($fields, ",");
                if (empty($fields))
                {
                    return '';
                }
            }
            else
            {
                $fields = $field;
            }

            $sql = "ALTER TABLE ".
                $this->db->_protect_identifiers($table).
                " ADD CONSTRAINT ".$this->db->_protect_identifiers($unique_table).
                " UNIQUE "."(".$this->db->_protect_identifiers($fields).")";

            if ($this->db->query($sql) === FALSE)
            {
                return FALSE;
            }
        }

        return TRUE;
    }
}

/* End of file oci8_forge.php */
/* Location: ./third_party/mdi/system/database/drivers/oci8/oci8_forge.php */