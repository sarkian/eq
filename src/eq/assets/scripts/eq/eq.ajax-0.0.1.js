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
        EQ.trigger('ajax.initialized');
        $(window).bind('popstate', function(e) {
            EQ.ajax.reload(function() {
                $(window).scrollTop(0);
            });
        });
    };

    Ajax.prototype.loadTo = function(sel, url, callback) {
        var uri = new URI(url);
        uri.query.ajax = true;
        $(sel).load(uri.toString(), function() {
            if(typeof callback === 'function')
                callback();
        });
    };

    Ajax.prototype.load = function(url, callback) {
        EQ.trigger('ajax.load');
        var uri = new URI(url);
        uri.query.ajax = true;
        this.container.load(uri.toString(), function() {
            if(typeof callback === 'function')
                callback();
            EQ.trigger('ajax.ready');
        });
    };

    Ajax.prototype.follow = function(link, callback) {
        var url;
        if(typeof link === 'object') {
            var el;
            if(link instanceof jQuery) {
                if(!link.length)
                    throw 'Empty jQuery object';
                el = link[0];
            }
            else
                el = link;
            if(el.hasAttribute('href'))
                url = el.href;
            else if(el.hasAttribute('data-href'))
                url = el.getAttribute('data-href');
            else
                throw 'Cant find href attribute';
        }
        else
            url = link;
//        if(url === window.location.href)
//            return;
        EQ.ajax.load(url, function() {
            if(window.history && history.pushState)
                history.pushState(null, '', url);
            if(typeof callback === 'function')
                if(callback() === false)
                    return;
            $(window).scrollTop(0);
        });
    };

    Ajax.prototype.bind = function(selector, callback) {
        return $(selector).click(function(e) {
            if(e.button == 1)
                return true;
            EQ.ajax.follow(this, callback);
            return false;
        });
    };

    Ajax.prototype.reload = function(callback) {
        EQ.trigger('ajax.reload');
        EQ.ajax.load(window.location.href, callback);
    };

    Ajax.prototype.url = function(path, params) {
        var page_url = new URI(document.location.href);
        var url = new URI(EQ.data.get('ajax.url_prefix', '/ajax') + '/' + path);
        url.query._t = EQ.data.get('ajax.token', '');
        if(page_url.query.hasOwnProperty('EQ_RECOVERY'))
            url.query.EQ_RECOVERY = null;
        if(typeof params === 'object')
            url.query = $.extend(url.query, params);
        return url.toString();
    };

    Ajax.prototype.exec = function(path, params, _options, aopts) {
        if(typeof _options === 'function')
            _options = {on_success: _options };
        var options = $.extend(true, {
            type: 'POST',
            is_url: false,
            on_success: null,
            on_error: null,
            on_warning: null,
            reload_on: {success: false, error: false},
            notify_on: {loading: false, success: false, error: true, warning: true}
        }, _options);
        var url = options.is_url ? path : EQ.ajax.url(path, options.type === 'GET' ? params : {});
        var on_success = function(message, data) {
            if(typeof options.on_success === 'function') {
                if(options.on_success(message, data) === false)
                    return;
            }
            if(options.notify_on.loading) {
                if(options.notify_on.success)
                    EQ.notify.loadingSuccess(message);
                else
                    EQ.notify.loadingEnd();
            }
            else if(options.notify_on.success)
                EQ.notify(message, 'success');
            if(options.reload_on.success)
                EQ.ajax.reload();
        };
        var on_error = function(message, data) {
            if(typeof options.on_error === 'function') {
                if(options.on_error(message, data) === false)
                    return;
            }
            if(options.notify_on.loading) {
                if(options.notify_on.error)
                    EQ.notify.loadingError(message);
                else
                    EQ.notify.loadingEnd();
            }
            else if(options.notify_on.error)
                EQ.notify(message, 'error');
            if(options.reload_on.error)
                EQ.ajax.reload();
        };

        if(options.notify_on.loading)
            EQ.notify.loadingBegin(typeof options.notify_on.loading === 'string'
                ? options.notify_on.loading : null);

        var opts = $.extend(true, {
            type: options.type,
            url: url,
            data: params,
            dataType: 'json',
            success: function(data) {
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
            },
            error: function(data) {
                if(data.statusText !== 'abort') {
                    console.error(data.statusText);
                    on_error(EQ.t('Application error'), data);
                }
            }
        }, aopts);

        return $.ajax(opts);

    };


    EQ.registerComponent('ajax', Ajax);
    $(function() {
        EQ.ajax.init();
    });

})();

