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
        var page_url = new URI(document.location.href);
        var url = new URI(EQ.data.get('ajax.url_prefix', '/ajax') + '/' + path);
        if(page_url.query.hasOwnProperty('EQ_RECOVERY'))
            url.query.EQ_RECOVERY = null;
        return url.toString();
    };

    Ajax.prototype.exec = function(path, _options) {
        var url = EQ.ajax.url(path);
        var options = $.extend(true, {
            data: {},
            on_success: null,
            on_error: null,
            on_warning: null,
            reload_on: {success: true, error: false},
            notify_on: {success: false, error: true, warning: true}
        }, _options);
        var on_success = function(message, data) {
            if(typeof options.on_success === 'function') {
                if(options.on_success(message, data) === false)
                    return;
            }
            if(options.notify_on.success)
                EQ.notify(message, 'success');
            if(options.reload_on.success)
                EQ.ajax.reload();
        };
        var on_error = function(message, data) {
            if(typeof options.on_error === 'function') {
                if(options.on_error(message, data) === false)
                    return;
            }
            if(options.notify_on.error)
                EQ.notify(message, 'error');
            if(options.reload_on.error)
                EQ.ajax.reload();
        };
        $.post(url, options.data, 'json').done(function(data) {
            if(data.success)
                on_success(data.message, data.data);
            else
                on_error(data.message, data.data);
            if(typeof data.warnings !== 'object')
                return;
            for(var i in data.warnings) {
                if(!data.warnings.hasOwnProperty(i))
                    continue;
                var msg = data.warnings[i];
                if(typeof msg !== 'string' || !msg.length)
                    continue;
                if(typeof options.on_warning === 'function')
                    options.on_warning(msg);
                if(options.notify_on.warning)
                    EQ.notify(msg, 'notice');
            }
        }).fail(function(data) {
            on_error(EQ.t('Application error'), data);
        });
    };


    EQ.registerComponent('ajax', Ajax);
    $(function() {
        EQ.ajax.init();
    });

})();

