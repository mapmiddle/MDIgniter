/**
 * Created by middle on 15. 1. 16.
 */


$(document).ready(function(){
    $( "#console-form" ).submit(function(e) {
        e.preventDefault();

        var $display = $(this).find("#console-display");
        var $command_line = $(this).find("#console-command-line");

        var url = e.currentTarget.action;
        var data = {
            "commandLine" : $command_line.val()
        };

        $.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: data,
            success: function(data) {
                var preText = $display.val();
                if (preText) {
                    preText += '\n';
                }

                $display.val(preText + data.data);
            },

            error: function (request, status, error) {
                $display.val(error);
            }
        });

        $command_line.val('');
    });
});