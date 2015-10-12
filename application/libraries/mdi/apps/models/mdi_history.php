<?php



class MDI_History extends MDI_Model {
    var $table = 'mdi_histories';
    var $using_history = FALSE;
    var $validation = array(
        'model_name' => array(
            'label' => 'Model Name', ///
            'rules' => array('varchar' => 64, 'required', )
        ),

        'model_id' => array(
            'label' => 'Model Id', ///
            'rules' => array('int', 'required', )
        ),

        'action' => array(
            'label' => 'Action', ///
            'rules' => array('varchar' => 16, 'required', 'choices' => array(
                'new' => 'new', ///
                'modify' => 'modify', ///
                'delete' => 'delete', ///
            ), )
        ),
    );

    var $has_one = array(
        'user' => array(
            'label' => 'User',
            'class' => MDI_USER_MODEL,
            'other_field' => 'history_list'
        ),
    );
};