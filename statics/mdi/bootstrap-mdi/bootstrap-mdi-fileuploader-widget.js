/**
 * Created by middle on 15. 2. 3.
 */

$(document).ready(function(){
    $('.mdi-fileuploader-input').mdi_fileuploader({
        create: function(that) {
            var $this = $(this);
            var $container = $this.closest(".mdi-fileuploader-container");
            var $button = $container.find('.mdi-fileuploader-button');
            var $input = $container.find('.mdi-fileuploader-input');

            $input.click(function(e){
                if ($button.attr('disabled')) {
                    e.preventDefault();
                }
            });
        },

        prepopulated: function() {
            var $this = $(this);
            var $container = $this.closest(".mdi-fileuploader-container");
            var $prepopulated = $container.find('.mdi-fileuploader-prepopulated');

            if ($prepopulated.length != 0) {
                return JSON.parse($prepopulated.text());
            }

            return null;
        },

        add: function(e, data) {
            var $this = $(this);
            var $form = $this.closest("form");
            var $container = $this.closest(".mdi-fileuploader-container");
            var $preview = $container.find('.mdi-fileuploader-preview');
            var mdi_options = data.getMDIOptions();
            var $img = null;

            if (data.hasOwnProperty('prepopulated'))  {
                $img = $('<img/>').attr({
                    src: data.prepopulated
                }).css({
                    maxWidth:'inherit',
                    maxHeight:'inherit'
                });

            } else if (data.hasOwnProperty('img')) {
                if (data.hasOwnProperty('preview')) {
                    $img = $('<img/>').attr({
                        src: data.preview.toDataURL()
                    });

                } else {
                    $img = $(data.img)
                        .removeAttr('width')
                        .removeAttr('height');
                }

                $img.css({
                    maxWidth:'inherit',
                    maxheight:'inherit'
                });

            } else if (data.files) {
                $img = $('<img/>').attr({}).css({
                    maxWidth:'inherit',
                    maxheight:'inherit'
                });
            } else {
                return;
            }

            var $item = $('<div/>').addClass('item')
                .append($('<div/>').addClass('canvas').append($img));

            var $remove = $('<div/>').addClass('remove')
                .append($('<i/>').addClass("glyphicon glyphicon-remove"))
                .css('cursor', 'pointer');

            $remove.click(function(e){
                data.trigger(
                    'remove',
                    $.Event('remove', {delegatedEvent: e}),
                    data
                );
            });

            $item.append($remove);

            // add prepopulated
            if (data.hasOwnProperty('prepopulated')) {
                $item.append($('<input/>').attr({
                    name: data.getParamName('prepopulated'),
                    type: 'hidden'
                }).val(data.prepopulated));
            }

            $preview.append($item);
            data.mdi_fileuploader_preview_item = $item;
        },

        remove: function(e, data) {
            var $this = $(this);
            var $form = $this.closest("form");
            var $container = $this.closest(".mdi-fileuploader-container");
            var mdi_options = data.getMDIOptions();

            if (!mdi_options.async) {
                /*
                if (mdi_options.singleFile) {
                    if (data.hasOwnProperty('fileInput')) {
                        data.getThat()._replaceFileInput(data);
                    }
                } else {

                }
                */

                if (data.hasOwnProperty('fileInput')) {
                    var files = $.makeArray(data.fileInput.prop('files'));

                    if (files.length > 1) {
                        // middle.fixme
                        // 문제의 지점
                        data.fileInput.remove();
                    } else {
                        data.fileInput.remove();
                    }
                }

                if (data.hasOwnProperty('prepopulated')) {
                    $container.append($('<input/>').attr({
                        name: data.getParamName('delete'),
                        type: 'hidden'
                    }).val(data.prepopulated));
                }
            }

            if (data.mdi_fileuploader_preview_item) {
                data.mdi_fileuploader_preview_item.remove();
            }
        },

        refresh: function(e, data) {
            var $this = $(this);
            var $container = $this.closest(".mdi-fileuploader-container");
            var $button = $container.find('.mdi-fileuploader-button');
            var options = data.getThat().options;

             if ($.type(options.maxNumberOfFiles) === 'number'&&
                 (options.getNumberOfFiles() || 0) >= options.maxNumberOfFiles)
             {
                 $button.attr('disabled', true);
             } else {
                 $button.removeAttr('disabled');
             }
        },

        done: function (e, data) {
            /*
            $.each(data.result.files, function (index, file) {
                $('<p/>').text(file.name).appendTo(document.body);
            });
            */
        }
    });
});