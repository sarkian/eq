(function() {

    var Dependent = function(obj, callback, onload) {

        this.obj = obj;
        this.callback = callback;

        this.obj.on('load', function(data) {
            if(typeof onload === 'function') {
                if(onload.apply(obj, [data]) === false)
                    return;
            }
            if(this.getOption(0).length)
                this.setValue(0);
            this.enable();
        });

    };


    Dependent.prototype.process = function(value, parent) {
        if(!value.length)
            return;
        this.obj.disable();
        this.obj.clearOptions();
        if(value == 0)
            return;
        this.callback.apply(this.obj, [value, parent]);
    };



    var DependentAjax = function(objects, path, pfunc) {

        this.objects = [];

        function bind(obj) {
            obj.obj.on('load', function(data) {
                if(typeof obj.onload === 'function') {
                    if(obj.onload.apply(obj.obj, [data]) === false)
                        return;
                }
                if(this.getOption(0).length)
                    this.setValue(0);
                this.enable();
            });
        }

        for(var i = 0; i < objects.length; i++) {
            bind(objects[i]);
            this.objects.push(objects[i]);
        }

        this.path = path;
        this.pfunc = pfunc || function() { return {} };

        this.xhr = null;

    };


    DependentAjax.prototype._applyData = function(data) {
        for(var i = 0; i < this.objects.length; i++) {
            (function(obj) {
                obj.obj.load(function(callback) {
                    callback(obj.datafunc(data));
                });
            })(this.objects[i]);
        }
    };


    DependentAjax.prototype.process = function(value, parent) {
        if(!value.length)
            return;
        for(var i = 0; i < this.objects.length; i++) {
            this.objects[i].obj.disable();
            this.objects[i].obj.clearOptions();
        }
        if(value == 0)
            return;
        var self = this;
        var url = EQ.ajax.url(this.path, this.pfunc(value));
        var data = EQ.data.get('selectize.ajax.' + url);
        if(data === undefined) {
            this.xhr = EQ.ajax.exec(url, {}, {
                is_url: true,
                reload_on: {success: false},
                on_success: function(msg, data) {
                    EQ.data.set('selectize.ajax.' + url, data);
                    self._applyData(data);
                }
            });
        }
        else {
            this._applyData(data);
        }
    };



    var setup = Selectize.prototype.setup;

    Selectize.prototype.setup = function() {
        setup.apply(this, arguments);

        this.dependent = [];
        this.dependent_ajax = [];
        this.on('change', function(value) {
            var i;
            for(i = 0; i < this.dependent.length; i++)
                this.dependent[i].process(value, this);
            for(i = 0; i < this.dependent_ajax.length; i++)
                this.dependent_ajax[i].process(value, this);
        });

        if(this.settings.clearOnOpen) {
            this.on('dropdown_open', function() {
                this._saved_value = this.getValue();
                this.clear();
            });
            this.on('dropdown_close', function() {
                if(this._saved_value && this.getValue() === "")
                    this.setValue(this._saved_value);
            });
        }
    };


    Selectize.prototype.loadAjax = function(path, params, options) {
        if(!this.hasOwnProperty('_xhr'))
            this._xhr = null;
        var df = function(data) { return data; };
        if(typeof options === 'boolean') {
            options = {
                use_cache: options, callback: function() {}, datafunc: df
            };
        }
        else if(typeof options === 'function') {
            options = {use_cache: true, callback: options, datafunc: df};
        }
        else {
            options = $.extend({
                use_cache: true,
                callback: function() {},
                datafunc: df
            }, options);
        }
        this.load(function(callback) {
            var url = EQ.ajax.url(path, params);
            var items = options.use_cache ? EQ.data.get('selectize.ajax.' + url) : undefined;
            if(items === undefined) {
                this._xhr && this._xhr.abort();
                this._xhr = EQ.ajax.exec(url, {}, {
                    is_url: true,
                    reload_on: {success: false},
                    on_success: function(msg, data) {
                        callback(options.datafunc(data));
                        options.callback();
                        if(options.use_cache)
                            EQ.data.set('selectize.ajax.' + url, data);
                    }
                });
            }
            else {
                callback(options.datafunc(items));
                options.callback();
            }
        });
    };


    Selectize.prototype.addDependent = function(obj, callback, onload) {
        this.dependent.push(new Dependent(obj, callback, onload));
    };


    Selectize.prototype.addDependentAjax = function(objects, path, pfunc) {
        this.dependent_ajax.push(new DependentAjax(objects, path, pfunc));
    };


    var onOptionSelect = Selectize.prototype.onOptionSelect;
    Selectize.prototype.onOptionSelect = function(e) {
        if(this.settings.unselectOnClick && this.$input.attr('multiple')) {
            var value = $(e.currentTarget).data('value');
            if(this.getItem(value).length) {
                this.removeItem(value);
                this.getOption(value).removeClass('selected');
                if(e.preventDefault) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
            else
                onOptionSelect.apply(this, arguments);
        }
        else
            onOptionSelect.apply(this, arguments);
    };



    $.fn.selectizeFirst = function(options) {
        this.selectize(options);
        return this[0].selectize;
    };

})();


