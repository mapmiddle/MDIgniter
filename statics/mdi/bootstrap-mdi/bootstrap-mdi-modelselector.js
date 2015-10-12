/**
 * Created by middle on 15. 1. 18.
 */

var MDI_MODELSELECTOR_DEFAULT_OPTIONS = {
    'multiple': false
};

$.fn.mdi_modelselector = function(options) {
    options = $.extend({}, MDI_MODELSELECTOR_DEFAULT_OPTIONS, options || {});

    var fn_popup_open = function(url, model, data, value, submit){
        var popup_url = url + "?model=" + model;
        var cw = screen.availWidth;     // screen width
        var ch = screen.availHeight;    // screen height
        var sw = 370;                   // popup width
        var sh = 500;                   // popup height
        var ml = window.screenX + (cw-sw)/2; // popup top
        var mt = window.screenY + (ch-sh)/2; // popup left

        var popup = window.open(popup_url, "", 'width='+sw+',height='+sh+',top='+mt+',left='+ml+',resizable=no,scrollbars=yes');
        popup.init_data = data;
        popup.init_value = value;
        popup.submit = submit;
        popup.focus();
    };

    var fn_get_empty_input_count = function($model_selector) {
        var empty_input = $model_selector.find('.input-group input[value=""].value');
        return empty_input.length;
    };

    var fn_create_modelselector_item = function($model_selector) {
        var name = $model_selector.data('name');
        var $new_input_group = $('<div/>').addClass('input-group');
        $('<span/>').addClass('input-group-addon cursor-hand search').append(
            $('<span/>').addClass('glyphicon glyphicon-search')
        ).appendTo($new_input_group);

        $('<input/>').attr({type: 'text', class:'form-control text'}).appendTo($new_input_group);
        $('<input/>').attr({type: 'hidden', class:'value', name:name, value:''}).appendTo($new_input_group);

        $new_input_group.appendTo($model_selector);
        fn_modelselector_item($model_selector, $new_input_group);
    };

    var fn_modelselector_item = function($model_selector, $input_group){
        var url_search = $model_selector.data('urlSearch');
        var url_link = $model_selector.data('urlLink');
        var model = $model_selector.data('model');
        var $this = $input_group;
        var $search = $this.find('.search');
        var $text = $this.find('.text');
        var $value = $this.find('.value');

        $text.on('click focus', function(){
            $this.blur();
        });

        var fn_submit = function(id, label, data){
            var $data = $this.find('.data');
            $text.val(label);
            $value.val(id);
            if ($data.length == 0) {
                $data = $('<input/>').attr({
                    type: 'hidden',
                    class: 'data'
                }).appendTo($this);
            }

            $data.text(JSON.stringify(data));
            fn_refresh();
        };

        var fn_refresh = function() {
            var $link = $this.find('.link');
            var $delete = $this.find('.delete');
            $link.remove();
            $delete.remove();


            if ($value.val()) {
                // link button
                $link = $('<span/>').addClass('input-group-addon cursor-hand link').append(
                    $('<span/>').addClass('glyphicon glyphicon-link')
                );

                $link.click(fn_link);
                $link.appendTo($this);

                // delete button
                if (options.multiple) {
                    $delete = $('<span/>').addClass('input-group-addon cursor-hand delete').append(
                        $('<span/>').addClass('glyphicon glyphicon-minus')
                    );
                } else {
                    $delete = $('<span/>').addClass('input-group-addon cursor-hand delete').append(
                        $('<span/>').addClass('glyphicon glyphicon-remove')
                    );
                }

                $delete.click(fn_remove);
                $delete.appendTo($this);
            }

            if (options.multiple) {
                if (fn_get_empty_input_count($model_selector) == 0) {
                    fn_create_modelselector_item($model_selector);
                }
            }
        };

        var fn_link = function() {
            var id = $value.val();
            if (id) {
                window.location.href = url_link + '/' + model + '/' + id;
            }
        };

        var fn_remove = function() {
            var $data = $this.find('.data');
            $text.val('');
            $value.val('');
            $data.remove();

            if (options.multiple) {
                if (fn_get_empty_input_count($model_selector) > 1) {
                    $this.remove();
                }
            }

            fn_refresh();
        };

        var fn_search = function(){
            var $data = $this.find('.data');
            var data = null;
            if ($data.length != 0) {
                data = JSON.parse($data.text());
            }

            var value = null;
            if ($value.length != 0) {
                value = $value.val();
            }

            fn_popup_open(url_search, model, data, value, fn_submit);
        };

        $search.click(fn_search);
        $text.click(fn_search);

        fn_refresh();
    };

    return this.each(function () {
        var $model_selector = $(this);
        var $input_group = $(this).find('.input-group');

        $input_group.each(function(){
            fn_modelselector_item($model_selector, $(this));
        });
    });
};

