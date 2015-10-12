<?php


class MDINull {
    public function __toString() {
        return '';
    }
};


if ( !function_exists('is_mdinull')){
    function is_mdinull($object) {
        return (isset($object) && is_object($object) && get_class($object) == 'MDINull');
    }
}
