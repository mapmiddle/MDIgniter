<?php



class MDI_Log extends MDI_Model {
    var $table = 'mdi_logs';
    var $using_history = FALSE;
    var $validation = array(
        'text' => array(
            'label' => 'Text', ///
            'rules' => array('text', 'required', )
        ),
    );

    public static function write($text) {
        $log = new MDI_Log();
        $log->text = $text;
        $log->save();
    }
};