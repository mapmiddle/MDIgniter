/**
 * Created by middle on 15. 2. 10.
 */

var MDI_AUTOCOMPLETE_DEFAULT_OPTIONS = {
    autocompleteDelay: 100,
    delimiter: ';',
    duplication: false,
    autocompleteSource: function() { return []; },
    readonlySource:  function() { return []; },
    prepopulated:  function() { return []; },
    refresh: function (e) {}
};

$.fn.mdi_autocomplete = function(options) {
    if (!(options instanceof Object)) {
        return $(this).tokenfield.apply($(this), arguments);
    }

    return this.each(function(){
        var mdi_options = {};
        var autocomplete_options = {};

        mdi_options = $.extend({}, MDI_AUTOCOMPLETE_DEFAULT_OPTIONS, options || {});

        if (!$.fn.tokenfield) {
            return;
        }

        var $this = $(this);
        var data = $(this).data();

        for(var index in data) {
            if (data.hasOwnProperty(index)) {
                var value = data[index];
                if (!(index in MDI_AUTOCOMPLETE_DEFAULT_OPTIONS)) {
                    autocomplete_options.options[index] = value;
                } else {
                    mdi_options[index] = value;
                }
            }
        }

        for(index in mdi_options) {
            if (mdi_options.hasOwnProperty(index)) {
                value = mdi_options[index];
                if (!(index in MDI_AUTOCOMPLETE_DEFAULT_OPTIONS)) {
                    autocomplete_options[index] = value;
                    delete mdi_options[index];
                }
            }
        }

        autocomplete_options['allowEditing'] = false;
        autocomplete_options['delimiter'] = mdi_options['delimiter'];
        autocomplete_options['autocomplete'] = {
            source: mdi_options['autocompleteSource'] instanceof Function ? mdi_options['autocompleteSource']() : mdi_options['autocompleteSource'],
            delay: mdi_options['autocompleteDelay']
        };

        autocomplete_options['tokens'] = mdi_options['prepopulated'] instanceof Function ? mdi_options['prepopulated']() : mdi_options['prepopulated'];
        autocomplete_options['mdi_option'] = mdi_options;

        var fn_duplication_check = function(e) {
            if (mdi_options['duplication']) {
                return true;
            }

            var tokens = $this.tokenfield('getTokens');
            for (var i in tokens) {
                if (tokens.hasOwnProperty(i)) {
                    if (tokens[i].label == e.attrs.label) {
                        var $autocomplete_input = $(this).siblings('.ui-autocomplete-input');
                        $autocomplete_input.val('');
                        return false;
                    }
                }
            }

            return true;
        };

        var fn_remove_check = function(e) {
            var readonlySource = mdi_options['readonlySource'] instanceof Function ? mdi_options['readonlySource']() : mdi_options['readonlySource'];

            for (var i in readonlySource) {
                if (readonlySource.hasOwnProperty(i)) {
                    if (readonlySource[i] == e.attrs.label) {
                        return false;
                    }
                }
            }

            return true;
        };

        var fn_refresh = function(e) {
            $this.attr({value:$this.tokenfield('getTokensList')});
            mdi_options.refresh.call(this, e);
        };

        $this
            .on('tokenfield:initialize', function (e) {
                var $this = $(this);
                var data = $(this).data('bs.tokenfield');
                var tokens = data.options.tokens;
                var readonlySource = data.options.mdi_option['readonlySource'] instanceof Function
                    ? data.options.mdi_option['readonlySource']() : data.options.mdi_option['readonlySource'];

                for (var i in tokens) {
                    if (tokens.hasOwnProperty(i)) {
                        for (var j in readonlySource) {
                            if (readonlySource.hasOwnProperty(j)) {
                                if (tokens[i] == readonlySource[j]) {
                                    var $token_label = $this.closest('.form-control.tokenfield').find('.token .token-label').filter(function(text) {
                                        return function() {
                                            return $(this).text() === text
                                        };
                                    }(readonlySource[j]));

                                    var $token = $token_label.closest('.token');
                                    $token.addClass('readonly');

                                    var $anchor = $token_label.siblings('a');
                                    $anchor.bind('click', false);
                                }
                            }
                        }
                    }
                }

            })

            .on('tokenfield:createtoken', function (e) {
                return fn_duplication_check.call(this, e);
            })

            .on('tokenfield:createdtoken', function (e) {
                fn_refresh.call(this, e);
            })

            .on('tokenfield:edittoken', function (e) {
                fn_refresh.call(this, e);
            })

            .on('tokenfield:removetoken', function (e) {
                if (!fn_remove_check.call(this, e)) {
                    e.preventDefault();
                }
            })

            .on('tokenfield:removedtoken', function (e) {
                fn_refresh.call(this, e);
            });

        $this.tokenfield(autocomplete_options);
    });
};