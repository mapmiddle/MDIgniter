/**
 * Created by middle on 15. 2. 2.
 */

//middle.fixme

var MDI_FILEUPLOADER_DEFAULT_OPTIONS = {
    'singleFile': false,
    'multiple': false,
    'async': false,
    'asyncUrl': null,
    'asyncUploadImmediately': false,
    'imageOnly': false,
    'imagePreview': true,
    'imagePreviewMaxWidth' : 160,
    'imagePreviewMaxHeight' : 160,
    'maxNumberOfFiles' : null,
    'create' : function(that) {},
    'prepopulated' : function() { return null; },
    'add' : function(e, data) {},
    'remove' : function(e, data) {},
    'refresh' : function() {}
};

$.fn.mdi_fileuploader = function(options) {
    var fn_is_filename_array = function($filename) {
        var $filter =  /^.+\[\]$/;
        return $filter.test($filename);
    };

    return this.each(function () {
        var mdi_options = {};
        var fileupload_options = {};

        mdi_options = $.extend({}, MDI_FILEUPLOADER_DEFAULT_OPTIONS, options || {});
        fileupload_options = {};

        if (!$.fn.fileupload) {
            return;
        }

        var $this = $(this);
        var data = $(this).data();

        for(var index in data) {
            if (data.hasOwnProperty(index)) {
                var value = data[index];
                if (!(index in MDI_FILEUPLOADER_DEFAULT_OPTIONS)) {
                    fileupload_options.options[index] = value;
                } else {
                    mdi_options[index] = value;
                }
            }
        }

        for(index in mdi_options) {
            if (mdi_options.hasOwnProperty(index)) {
                value = mdi_options[index];
                if (!(index in MDI_FILEUPLOADER_DEFAULT_OPTIONS)) {
                    fileupload_options[index] = value;
                    delete mdi_options[index];
                }
            }
        }

        if (!$this.attr('name')) {
            $this.attr('name', 'files[]');
        }

        var name = $this.attr('name');
        if (mdi_options['singleFile']) {
            if (fn_is_filename_array(name)) {
                $this.attr('name', name.replace(/\[\]/g, ''));
            }

            mdi_options['multiple'] = false;
            fileupload_options['maxNumberOfFiles'] = 1;
        } else {
            if (!fn_is_filename_array(name)) {
                $this.attr('name', name + '[]');
            }
        }

        name = $this.attr('name');

        // middle.comment
        // middle.fixme
        // multiple is not available
        if (mdi_options['multiple'] && mdi_options['async']) {
            $this.attr('multiple', '');
        }

        if (mdi_options['imageOnly']) {
            $this.attr('accept', 'image/*');
        }

        if (mdi_options['asyncUrl']) {
            fileupload_options['url'] = mdi_options['asyncUrl'];
        }

        // image process
        if (mdi_options['imageOnly']) {
            fileupload_options['acceptFileTypes'] = /^image\/(gif|jpeg|png|svg\+xml)$/;
            fileupload_options['loadImageFileTypes'] = /^image\/(gif|jpeg|png|svg\+xml)$/;
        }

        if (mdi_options['imagePreview']) {
            fileupload_options['disableImagePreview'] = false;
        }

        if (mdi_options['imagePreviewMaxWidth']) {
            fileupload_options['previewMaxWidth'] = mdi_options['imagePreviewMaxWidth'];
        }

        if (mdi_options['imagePreviewMaxHeight']) {
            fileupload_options['previewMaxHeight'] = mdi_options['imagePreviewMaxHeight'];
        }

        if (mdi_options['maxNumberOfFiles'] && !mdi_options['singleFile']) {
            fileupload_options['maxNumberOfFiles'] = mdi_options['maxNumberOfFiles'];
        }

        fileupload_options['replaceFileInput'] = true;
        fileupload_options['disableImageReferencesDeletion'] = true;
        fileupload_options['mdi_options'] = mdi_options;

        $.widget('mdi._mdi_fileuploader', $.blueimp.fileupload, {
            options: {
                add: function(e, data) {
                    var that = data.getThat();
                    var $this = $(this);

                    /*
                     if (mdi_options['imageOnly']) {
                     var uploadFile = data.files[0];
                     if (!(/\.(gif|jpg|jpeg|tiff|png)$/i).test(uploadFile.name)) {
                     return;
                     }
                     }
                     */

                    data.process(function () {
                        return $this._mdi_fileuploader('process', data);
                    }).done(function(data){
                        data.id = that.data_id_pool;
                        that.datalist[that.data_id_pool++] = data;
                        data.getMDIOptions()['add'].call($this[0], e, data);

                        if (!mdi_options['async']) {
                            if (!('replaceFileInput' in fileupload_options) || fileupload_options['replaceFileInput']) {
                                if (data.fileInput) {
                                    var $form = $this.closest("form");
                                    $form.append(data.fileInput.css({display: 'none'}));
                                }
                            }
                        } else {
                            if (mdi_options['asyncUploadImmediately']) {
                                data.submit();
                            }
                        }

                        data.trigger(
                            'refresh',
                            $.Event('refresh', {delegatedEvent: e}),
                            data
                        );
                    });
                },

                remove: function(e, data) {
                    var that = data.getThat();
                    var $this = $(this);

                    data.getMDIOptions()['remove'].call($this[0], e, data);
                    delete that.datalist[data.id];

                    data.trigger(
                        'refresh',
                        $.Event('refresh', {delegatedEvent: e}),
                        data
                    );
                },

                refresh: function(e, data) {
                    var $this = $(this);
                    data.getMDIOptions()['refresh'].call($this[0], e, data);
                }
            },

            _create: function () {
                this._super();

                var that = this;
                that.data_id_pool = 0;
                that.datalist = {};

                that.options['getNumberOfFiles'] = function(){
                    return Object.keys(that.datalist).length
                };

                if (that.options.mdi_options['create']) {
                    that.options.fileInput.each(function(){
                        var create = that.options.mdi_options['create'];
                        if (create instanceof Function) {
                            create.call(this, that);
                        }
                    });
                }

                if (that.options.mdi_options['prepopulated']) {
                    that.options.fileInput.each(function(){
                        var prepopulated = that.options.mdi_options['prepopulated'];
                        if (prepopulated instanceof Function) {
                            prepopulated = prepopulated.call(this, that);
                        }

                        if (prepopulated) {
                            that._onAdd(null, {prepopulated: prepopulated});
                        }
                    });
                }
            },

            _onAdd: function (e, data) {
                if (data.files) {
                    return this._super(e, data);
                }

                if (data.prepopulated) {
                    var that = this,
                        result = true,
                        options = $.extend({}, this.options, data),
                        prepopulated = data.prepopulated,
                        paramName = this._getParamName(options);

                    //data.originalFiles = null;
                    $.each(prepopulated, function (index, element) {
                        var newData = $.extend({}, data);
                        //newData.files = null;
                        newData.prepopulated = element;
                        newData.paramName = paramName;
                        that._initResponseObject(newData);
                        that._initProgressObject(newData);
                        that._addConvenienceMethods(e, newData);
                        result = that._trigger(
                            'add',
                            $.Event('add', {delegatedEvent: e}),
                            newData
                        );
                        return result;
                    });
                }

                return false;
            },

            // Adds convenience methods to the data callback argument:
            _addConvenienceMethods: function (e, data) {
                this._super(e, data);

                var that = this;
                if (data.hasOwnProperty('prepopulated')) {
                    data.process = function (resolveFunc, rejectFunc) {
                        return $.Deferred().resolveWith(that, [this]).promise();
                    };
                }

                data.getThat = function () {
                    return that;
                };

                data.getElements = function () {
                    return that.options.fileInput;
                };

                data.getParamName = function(optionName) {
                    var singular = true;
                    var name = $.type(data.paramName) === 'array' ?
                        data.paramName[0] : data.paramName;

                    var $filter =  /^.+\[\]$/;
                    if ($filter.test(name)) {
                        name = name.replace(/\[\]/g, '');
                        singular = false;
                    }

                    if (optionName) {
                        name += '-' + optionName;
                    }

                    if (!singular) {
                        name += '[]';
                    }

                    return name;
                };

                data.trigger = function() {
                    return that._trigger.apply(that, arguments);
                };

                data.getMDIOptions = function() {
                    return that.options.mdi_options;
                };

                /*
                data.submit = function () {
                    if (this.state() !== 'pending') {
                        data.jqXHR = this.jqXHR =
                            (that._trigger(
                                'submit',
                                $.Event('submit', {delegatedEvent: e}),
                                this
                            ) !== false) && that._onSend(e, this);
                    }
                    return this.jqXHR || that._getXHRPromise();
                };
                */
            },

            add: function (data) {
                this._super(data);

                if (!data || this.options.disabled) {
                    return;
                }

                if (data.fileInput && !data.files) {
                    this._super(data);
                } else if (data.files) {
                    this._super(data);
                }

                if (data.prepopulated) {
                    // prepopulated
                    data.prepopulated = $.makeArray(data.prepopulated);
                    this._onAdd(null, data);
                }
            }
        });

        $this._mdi_fileuploader(fileupload_options);
        //.bind('fileuploadprocessfail', function (e, data) {});
    });
};