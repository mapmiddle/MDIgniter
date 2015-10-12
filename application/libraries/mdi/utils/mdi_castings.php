<?php



if ( !function_exists('force_type_casting')){
    function force_type_casting($type, $value) {
        if ($type == 'boolean') {
            if (empty($value)) {
                $value = FALSE;
            } else if (is_numeric($value)) {
                $value = (boolean)$value;
            } else if (is_string($value)) {
                $value = strtoupper($value) == 'FALSE' ? FALSE : TRUE;
            } else {
                $value = (boolean)$value;
            }
        } else if ($type == 'int') {
            if (empty($value)) {
                $value = 0;
            } else {
                $value = (int)$value;
            }
        } else if ($type == 'string') {
            if (is_array($value)) {
                $value = implode(' ', $value);
            } else if(is_bool($value)) {
                $value = $value ? 'TRUE' : 'FALSE';
            } else {
                $value = (string)$value;
            }
        }

        return $value;
    }
}