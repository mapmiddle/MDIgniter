<?php


if ( !function_exists('template_var')){
    function template_var(&$var, $default) {
        if (!isset($var)) {
            $var = $default;
        }
    }
}

if ( !function_exists('get_bootstrap_12_grid_col_class')){
    function get_bootstrap_12_grid_col_class($type, $col_count) {
        if ($col_count == 5) { $index = '2-4'; }
        else if($col_count == 7) { $index = '1-7'; }
        else if($col_count == 8) { $index = '1-5'; }
        else if($col_count == 9) { $index = '1-3'; }
        else if($col_count >= 10) { $index = '1-2'; }
        else { $index = (string)((int)(12/$col_count)); }

        $class = 'col-'.$type.'-'.$index;
        return $class;
    }
}

if ( !function_exists('replace_param_url')){
    function replace_param_url() {
        //middle.FIXME
    }
}

if ( !function_exists('display_hidden_input')){
    function display_hidden_input($value) {
        if (is_array($value)) {
            // nothing
        } else {
            $value = array($value);
        }

        foreach($value as $k => $v) {
            if (is_numeric($k) || !isset($v)) {
                continue;
            }

            echo '<input type="hidden" name="'.$k.'"value="'.(string)$v.'"/>';
        }
    }
}


if ( !function_exists('make_mdi_paged')){
    function make_mdi_paged($array,
        $page = 1, $page_size = 50, $paginator_size = 10,
        $page_num_by_rows = FALSE, $info_object = 'paged') {

        if($page_num_by_rows) {
            $page = 1 + floor(intval($page) / $page_size);
        }

        // never less than 1
        $page = max(1, intval($page));
        $offset = $page_size * ($page - 1);
        $count = count($array);
        $remained = $count - ($offset + $page_size);

        $object = new stdClass();
        $object->all = array_slice($array, $offset, ($remained >= $page_size) ? ($page_size) : ($count - $offset));

        $paginator = array();
        $paginator_current_index = (($page-1)%$paginator_size);
        $paginator_begin = $page - $paginator_current_index;
        $total_pages = ceil($count / $page_size);
        //$total_pages = $this->{$info_object}->total_pages;

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

        $object->{$info_object} = new stdClass();
        $object->{$info_object}->paginator = $paginator;
        return $object;

    }
}

/*
if ( !function_exists('include_template')){
    function include_template($path) {
        $template_args = func_get_args();
        ob_start();
        include($path);
        return ob_get_clean();
    }
}
*/