/**
 * Created by middle on 15. 1. 19.
 */

$(document).ready(function(){
    var $search_init = $('#search-init');
    var $search_result = $('#search-result');
    var $search_failed = $('#search-failed');
    var is_searching = false;

    var fn_set_result = function(data, selected_id) {
        selected_id = selected_id || null;

        $search_init.hide();
        $search_failed.hide();
        $search_result.show();
        $search_result.empty();

        var $listitem_template = $('<a/>').attr({
            href: '#',
            class:'list-group-item'
        });

        for (var k in data.objects) {
            var id = data.objects[k].id;
            var label = data.objects[k].label;
            var $listitem = $listitem_template.clone();

            if (selected_id == id) {
                $listitem.addClass('active');
            }

            $listitem.text(label);
            $listitem.click(function(id, label){
                return function() {
                    try {
                        window.submit(id, label, data);
                    }
                    catch (err) {}
                    window.close();
                }
            }(id, label));

            $listitem.appendTo($search_result);
        }
    };

    $('#search-btn').click(function(){
        if (is_searching) {
            return true;
        }

        var searchParams = {};
        $('#pannel-input').find('input.form-control').each(function(){
            var name = $(this).attr('name');
            var value = $(this).val();

            if (value) {
                searchParams[name] = value;
            }
        });

        if ($.isEmptyObject(searchParams)) {
            alert('Must use at least one search options');
            return false;
        }

        is_searching = true;
        var ajax_url = $(this).data('url');
        var ajax_data = {
            model: $(this).data('model'),
            fields: searchParams
        };

        $.ajax({
            url: ajax_url,
            type: 'post',
            dataType: 'json',
            data: ajax_data,
            success: function(result) {
                is_searching = false;

                if (!result.success) {
                    alert(result.data);
                } else {
                    if (result.data.objects.length == 0) {
                        $search_init.hide();
                        $search_result.hide();
                        $search_failed.css('display', 'block');

                        alert('No results found');
                    } else {
                        fn_set_result(result.data);

                        if (result.data.continuous) {
                            alert('There are too many search results');
                        } else {
                            alert(result.data.count + 'results found');
                        }
                    }
                }
            },

            error: function (request, status, error) {
                is_searching = false;
                alert(error);
            }
        });

        return true;
    });

    if (window.init_data && window.init_value) {
        fn_set_result(window.init_data, window.init_value);
    }

});