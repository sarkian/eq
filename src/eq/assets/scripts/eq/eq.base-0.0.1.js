(function() {

    if(!window.eq)
        window.eq = {};


    // eq.Exception

    eq.Exception = function(message) {
        var e = Error.call(this, message);
        e.name = 'eq.Exception';
        return e;
    };

    eq.Exception.prototype = Object.create(Error.prototype, {
        constructor: { value: eq.Exception }
    });


    // eq.EQ

    var t = {
        components: {},
        registered_components: {},
        default_components: {
            'data': {
                get: function(name, _default) { return _default; },
                set: function(name, value) {}
            },
            't': function(str) {
                return  EQ.data.get('i18n.' + str, str);
            }
        },
        callbacks: {},
        notification_handler: null,
        nh_warning: false,
        jquery_wrapped: false
    };

    function defaultGetter(name) {
        return function() {
            return t.default_components[name];
        };
    }

    eq.EQ = function() {
        for(var i in t.default_components) {
            if(!t.default_components.hasOwnProperty(i))
                continue;
            this.__defineGetter__(i, defaultGetter(i));
        }
    };

    eq.EQ.prototype.registerComponent = function(name, cls, config) {
        if(this.isComponentRegistered(name))
            throw new eq.Exception('Component already registered: ' + name);
        if(typeof cls === 'function') {
            t.registered_components[name] = {
                'class':  cls,
                'config': config
            };
        }
        else if(typeof cls === 'object') {
            t.components[name] = cls;
        }
        this.__defineGetter__(name, function() {
            if(!t.components[name])
                t.loadComponent(name);
            return t.components[name];
        });
    };

    eq.EQ.prototype.isComponentRegistered = function(name) {
        return !!(t.components[name] || t.registered_components[name]);
    };

    eq.EQ.prototype.bind = function(names, callback) {
        if(typeof names !== 'object')
            names = [names];
        for(var i in names) {
            if(!names.hasOwnProperty(i))
                continue;
            var name = names[i];
            if(!t.callbacks[name])
                t.callbacks[name] = [];
            t.callbacks[name].push(callback);
        }
    };

    eq.EQ.prototype.unbind = function(name, callback) {
        if(!t.callbacks[name])
            return;
        if(callback) {
            for(var i in t.callbacks[name]) {
                if(!t.callbacks[name].hasOwnProperty(i))
                    continue;
                if(t.callbacks[name][i] === callback)
                    delete t.callbacks[name][i];
            }
        }
        else {
            t.callbacks[name] = [];
        }
    };

    eq.EQ.prototype.trigger = function(name, args) {
        if(!t.callbacks[name])
            return;
        if(!(args instanceof Array))
            args = [args];
        for(var i in t.callbacks[name]) {
            if(!t.callbacks[name].hasOwnProperty(i))
                continue;
            var callback = t.callbacks[name][i];
            callback.apply(callback, args);
        }
    };

    eq.EQ.prototype.dispatch = function(name) {
        if(window.Event && typeof window.dispatchEvent) {
            var evt = new Event(name);
            window.dispatchEvent(evt);
        }
    };

    eq.EQ.prototype.dispatchOn = function(on, name) {
        if(window.addEventListener) {
            var self = this;
            window.addEventListener(on, function() {
                self.dispatch(name);
            }, false);
        }
    };

    eq.EQ.prototype.setNotificationHandler = function(handler) {
        if(typeof handler !== 'function')
            throw new eq.Exception('Invalid notification handler');
        if(t.notification_handler)
            EQ.trigger('warning', 'Notification handler replaced');
        t.notification_handler = handler;
    };

    eq.EQ.prototype.notify = function(message, type, options) {
        if(!t.notification_handler && !t.nh_warning) {
            EQ.trigger('warning', 'Notification handler not specified');
            t.nh_warning = true;
        }
        switch(type) {
            case 'err':
            case 'error':
                type = 'error';
                break;
            case 'warn':
            case 'warning':
            case 'notice':
                type = 'notice';
                break;
            case 'done':
            case 'success':
                type = 'success';
                break;
            default:
                type = 'info';
        }
        if(!message) {
            switch(type) {
                case 'error':
                case 'warning':
                    message = EQ.t('Error');
                    break;
                case 'success':
                    message = EQ.t('Done');
                    break;
                default:
                    return;
            }
        }
        if(!t.notification_handler) {
            switch(type) {
                case 'error':
                    EQ.trigger('error', message);
                    break;
                case 'warning':
                    EQ.trigger('warning', message);
                    break;
                case 'success':
                    console.log(message);
                    break;
                default:
                    EQ.trigger('log', message);
            }
        }
        else
            t.notification_handler(message, type, options);
    };

    eq.EQ.prototype.wrapJQuery = function() {
        if(t.jquery_wrapped || !window.jQuery)
            return;
        t.jquery_wrapped = true;
        var ready = jQuery.fn.ready;
        jQuery.fn.ready = function(callback) {
            ready(function() {
                try {
                    callback.apply(callback, arguments);
                }
                catch(e) {
                    EQ.trigger('error', e);
                }
            });
        };
    };

    t.loadComponent = function(name) {
        if(!t.registered_components[name])
            throw new Error('Undefined component: ' + name);
        var c = t.registered_components[name];
        t.components[name] = new c.class(c.config);
    };


    window.EQ = new eq.EQ();
    EQ.wrapJQuery();

    EQ.bind(['err', 'error'], function(e) {
        if(console.error)
            console.error(e);
        else
            console.log(e);
    });

    EQ.bind(['warn', 'warning'], function(msg) {
        if(console.warn)
            console.warn(msg);
        else
            console.log(msg);
    });

    EQ.bind(['log', 'info'], function(msg) {
        console.log(msg);
    });

    window.EQ.dispatch('EQBaseReady');

})();



