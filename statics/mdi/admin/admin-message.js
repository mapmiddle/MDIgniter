/**
 * Created by middle on 15. 1. 17.
 */

$(document).ready(function(){
    $('#message-form').submit(function(){
        var $this = $(this);
        var data = $('#message-data').text();
        if (data) {
            data = JSON.parse(data);
            for (var name in data) {
                if (data[name] instanceof Array) {
                    for (var index in data[name]) {
                        $('<input/>', {
                            type: 'hidden',
                            name: name+'[]',
                            value: data[name][index]
                        }).appendTo($this);
                    }
                } else {
                    $('<input/>', {
                        type: 'hidden',
                        name: name,
                        value: data[name]
                    }).appendTo($this);
                }
            }
        }
    });
});
