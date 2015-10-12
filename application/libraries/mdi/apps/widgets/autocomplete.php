<?php


class AutoComplete {
    public function __construct() {
        //blank
    }

    function statics_lazy() {
        $ci =& get_instance();
        if ($ci->mdi->is_debug()) {
            $ci->mdi->statics_lazy('css', 'mdi/jquery/ui/1_11_2/jquery-ui.css');
            $ci->mdi->statics_lazy('css', 'mdi/bootstrap/css/bootstrap.min.css');
            $ci->mdi->statics_lazy('css', 'mdi/bootstrap-tokenfield/css/tokenfield-typeahead.css');
            $ci->mdi->statics_lazy('css', 'mdi/bootstrap-tokenfield/css/bootstrap-tokenfield.css');
            $ci->mdi->statics_lazy('css', 'mdi/bootstrap-mdi/bootstrap-mdi-autocomplete.css');
            $ci->mdi->statics_lazy('js', 'mdi/jquery/ui/1_11_2/jquery-ui.js');
            $ci->mdi->statics_lazy('js', 'mdi/bootstrap/js/bootstrap.min.js');
            $ci->mdi->statics_lazy('js', 'mdi/bootstrap-tokenfield/bootstrap-tokenfield.js');
            $ci->mdi->statics_lazy('js', 'mdi/twitter/typeahead.bundle.js');
            $ci->mdi->statics_lazy('js', 'mdi/bootstrap-mdi/bootstrap-mdi-autocomplete.js');
            $ci->mdi->statics_lazy('js', 'mdi/bootstrap-mdi/bootstrap-mdi-autocomplete-widget.js');
        } else {
            $ci->mdi->statics_lazy('css', 'mdi/jquery/ui/1_11_2/jquery-ui.min.css');
            $ci->mdi->statics_lazy('css', 'mdi/bootstrap/css/bootstrap.min.css');
            $ci->mdi->statics_lazy('css', 'mdi/bootstrap-tokenfield/css/tokenfield-typeahead.min.css');
            $ci->mdi->statics_lazy('css', 'mdi/bootstrap-tokenfield/css/bootstrap-tokenfield.min.css');
            $ci->mdi->statics_lazy('css', 'mdi/bootstrap-mdi/bootstrap-mdi-autocomplete.css');
            $ci->mdi->statics_lazy('js', 'mdi/jquery/ui/1_11_2/jquery-ui.min.js');
            $ci->mdi->statics_lazy('js', 'mdi/bootstrap/js/bootstrap.min.js');
            $ci->mdi->statics_lazy('js', 'mdi/bootstrap-tokenfield/bootstrap-tokenfield.min.js');
            $ci->mdi->statics_lazy('js', 'mdi/twitter/typeahead.bundle.min.js');
            $ci->mdi->statics_lazy('js', 'mdi/bootstrap-mdi/bootstrap-mdi-autocomplete.js');
            $ci->mdi->statics_lazy('js', 'mdi/bootstrap-mdi/bootstrap-mdi-autocomplete-widget.js');
        }
    }

    function input($kwargs=array()) {
        $name = array_get($kwargs['name'], 'autocomplete');
        $prepopulated = array_get($kwargs['prepopulated'], array());
        $readonly = array_get($kwargs['readonly'], array());
        $source = array_get($kwargs['source'], array());
        $duplication = array_get($kwargs['duplication'], FALSE);

        $container_name = 'mdi-autocomplete-container';
        $class_name = 'mdi-autocomplete';

        if (is_string($prepopulated)) {
            $prepopulated = preg_split( '/(;|\s)/', $prepopulated, -1, PREG_SPLIT_NO_EMPTY);
            $prepopulated = json_encode($prepopulated);
        } else if (is_array($prepopulated) && !empty($prepopulated)) {
            $prepopulated = json_encode($prepopulated);
        } else {
            $prepopulated = NULL;
        }

        if (is_string($readonly)) {
            $readonly = preg_split( '/(;|\s)/', $readonly, -1, PREG_SPLIT_NO_EMPTY);
            $readonly = json_encode($readonly);
        } else if (is_array($readonly) && !empty($readonly)) {
            $readonly = json_encode($readonly);
        } else {
            $readonly = NULL;
        }

        if (is_string($source)) {
            $source = preg_split( '/(;|\s)/', $source, -1, PREG_SPLIT_NO_EMPTY);
            $source = json_encode($source);
        } else if (is_array($source) && !empty($source)) {
            $source = json_encode($source);
        } else {
            $source = NULL;
        }

        $input = <<<CODE
        <div class="$container_name">
CODE;
        if ($prepopulated) {
            $input .= "<div class=\"mdi-autocomplete-prepopulated\" style=\"display:none;\">";
            $input .= $prepopulated;
            $input .= "</div>";
        }

        if ($readonly) {
            $input .= "<div class=\"mdi-autocomplete-readonly-source\" style=\"display:none;\">";
            $input .= $readonly;
            $input .= "</div>";
        }

        if ($source) {
            $input .= "<div class=\"mdi-autocomplete-source\" style=\"display:none;\">";
            $input .= $source;
            $input .= "</div>";
        }

        $input .= <<<CODE
        <input type="text" class="form-control $class_name" name="$name" value=""
CODE;

        $input .= $duplication ? "data-duplication=\"$duplication\"" : '';
        $input .= <<<CODE
        />
        </div>
CODE;

        echo $input;
    }
};