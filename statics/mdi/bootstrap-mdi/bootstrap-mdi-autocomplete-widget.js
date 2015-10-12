/**
 * Created by middle on 15. 2. 10.
 */

$(document).ready(function(){
    $('input.mdi-autocomplete').each(function(){
        var $input = $(this);
        var $container = $(this).closest('.mdi-autocomplete-container');
        var $autocomplete_source = $container.find('.mdi-autocomplete-source');
        var $autocomplete_readonly_source = $container.find('.mdi-autocomplete-readonly-source');
        var $prepopulated = $container.find('.mdi-autocomplete-prepopulated');

        $input.mdi_autocomplete({
            autocompleteSource: function(){
                if ($autocomplete_source.length != 0) {
                    return JSON.parse($autocomplete_source.text());
                }

                return [];
            },

            readonlySource: function(){
                if ($autocomplete_readonly_source.length != 0) {
                    return JSON.parse($autocomplete_readonly_source.text());
                }

                return [];
            },

            prepopulated: function(){
                if ($prepopulated.length != 0) {
                    return JSON.parse($prepopulated.text());
                }

                return [];
            },

            refresh: function(e){
                var options = { attrs: e.attrs, relatedTarget: e.relatedTarget };
                var editEvent = $.Event('mdi_autocomplete:refresh', options);
                $(this).trigger(editEvent);
            },

            autocompleteDelay: 100,
            showAutocompleteOnFocus: true
        });
    });
});