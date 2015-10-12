<?php



class MDI_Environment extends MDI_Model {
    var $table = 'environments';
    var $default_order_by = array('key' => 'asc');

    var $validation = array(
        'key' => array(
            'label' => 'Environment Key',///
            'rules' => array('varchar' => 64, 'required', 'trim', 'unique', 'index')
        ),

        'value' => array(
            'label' => 'Environment Value', ///
            'rules' => array('text')
        ),
    );

    function post_sync() {
        $ev = new MDI_Environment();
        $ev->key = 'GOOGLE_API_KEY';
        $ev->value = mdi::config('google_api_key');
        $ev->save();

        $ev = new MDI_Environment();
        $ev->key = 'APNS_PEM_PATH';
        $ev->value = mdi::config('apns_pem_path');
        $ev->save();

        $ev = new MDI_Environment();
        $ev->key = 'APNS_HOST';
        $ev->value = mdi::config('apns_host');
        $ev->save();

        $ev = new MDI_Environment();
        $ev->key = 'ENABLE_PUSH_MESSAGE';
        $ev->value = '1';
        $ev->save();
    }

    static public function get_value($key, $default=NULL) {
        $ev = new self();
        $ev->get_by_key($key);
        if ($ev->exists()) {
            return $ev->value;
        }

        return $default;
    }

    static public function get_or_create($key, $default=NULL) {
        $ev = new self();
        $ev->get_by_key($key);
        if ($ev->exists()) {
            return $ev;
        }

        $ev = new self();
        $ev->key = $key;
        $ev->value = $default;
        $ev->save();

        return $ev;
    }

}