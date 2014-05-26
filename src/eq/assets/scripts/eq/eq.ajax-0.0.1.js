(function() {

    function findContainer() {
        var container = $('body #content');
        if(container.length == 1)
            return container;
        container = $('body #contents');
        if(container.length == 1)
            return container;
        container = $('body #container');
        if(container.length == 1)
            return container;
        container = $('body > .content');
        if(container.length == 1)
            return container;
        container = $('body > .contents');
        if(container.length == 1)
            return container;
        container = $('body > .container');
        if(container.length == 1)
            return container;
        return $(document.body);
    }


    var Ajax = function() {

    };

    Ajax.prototype.init = function() {
        this.container = findContainer();
        EQ.trigger('ajax.ready');
    };

    Ajax.prototype.reload = function() {
        EQ.trigger('ajax.reload');
        var url = new URI(document.location.href);
        url.query.ajax = true;
        this.container.load(url.toString(), function() {
            EQ.trigger('ajax.ready');
        });
    };

    Ajax.prototype.url = function(path) {
//        if(EQ.isComponentRegistered('data'))
            return EQ.data.get('ajax.url_prefix', '/ajax') + '/' + path;
//        else
//            return '/ajax/' + path;
    };

    /**
     * exec(path, data, on_success, on_error)
     * exec(path, data)
     * exec(path, on_success)
     * exec(path, on_success, on_error)
     */
    Ajax.prototype.exec = function(path, _data, _on_success, _on_error, _reload) {
        var url = EQ.ajax.url(path),
            data = _data,
            on_success = _on_success,
            on_error = _on_error,
            reload = _reload;
        if(typeof _data === 'function') {
            on_success = _data;
            on_error = _on_success;
            data = {};
        }
        if(typeof _data === 'boolean' || typeof _data === 'string') {
            reload = _data;
            data = {};
        }
        if(typeof _on_success === 'boolean' || typeof _on_success === 'string') {
            reload = _on_success;
        }
        if(typeof _on_error === 'boolean' || typeof _on_error === 'string') {
            reload = _on_error;
        }
        var process_warns = function(warnings) {
            if(typeof warnings !== 'object')
                return;
            for(var i in warnings) {
                if(!warnings.hasOwnProperty(i))
                    continue;
                var msg = warnings[i];
                if(typeof msg === 'string' && msg.length)
                    EQ.notify(msg, 'warning');
            }
        };
        on_success = typeof _on_success === 'function' ? _on_success :
        function(message, data, warnings) {
            EQ.notify(message, 'success');
            process_warns(warnings);
            if(reload === true || reload === 'success')
                EQ.ajax.reload();
        };
        on_error = typeof _on_error === 'function' ? _on_error :
        function(message, data, warnings) {
            EQ.notify(message, 'error');
            process_warns(warnings);
            if(reload === true || reload === 'error')
                EQ.ajax.reload();
        };
        $.post(url, data, 'json').done(function(data) {
            if(data.success)
                on_success(data.message, data.data, data.warnings);
            else
                on_error(data.message, data.data, data.warnings);
        }).fail(function(data) {
            on_error(EQ.t('Application error', null, []));
            if(reload === true || reload === 'error')
                EQ.ajax.reload();
        });
    };


    EQ.registerComponent('ajax', Ajax);
    $(function() {
        EQ.ajax.init();
    });

})();

