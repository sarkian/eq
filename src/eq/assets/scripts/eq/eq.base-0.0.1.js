(function() {

    var eq = {};


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

    eq.EQ = function() {
        
    };

    var t = {
        components: {},
        registered_components: {},
        callbacks: {}
    };

    eq.EQ.prototype.registerComponent = function(name, cls, config) {
        if(t.components[name] || t.registered_components[name])
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

    eq.EQ.prototype.bind = function(name, callback) {
        if(!t.callbacks[name])
            t.callbacks[name] = [];
        t.callbacks[name].push(callback);
    };

    eq.EQ.prototype.unbind = function(name, callback) {
        if(callback) {
            for(var i in t.callbacks[name]) {
                if(t.callbacks[name][i] === callback)
                    delete t.callbacks[name][i];
            }
        }
        else {
            t.callbacks[name] = [];
        }
    };

    eq.EQ.prototype.trigger = function(name, args) {
        for(var i in t.callbacks[name]) {
            var callback = t.callbacks[name][i];
            callback.apply(callback, args);
        }
    };

    eq.EQ.prototype.dispatch = function(name) {
        if(window.Event && window.dispatchEvent) {
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

    t.loadComponent = function(name) {
        if(!t.registered_components[name])
            throw new Error('Undefined component: ' + name);
        var c = t.registered_components[name];
        t.components[name] = new c.class(c.config);
    };


    if(window.jQuery) {
        var ready = jQuery.fn.ready;
        jQuery.fn.ready = function(callback) {
            ready(function() {
                try {
                    callback.apply(callback, arguments);
                }
                catch(e) {
                    EQ.trigger('error', [e]);
                }
            });
        };
    }

    window.eq = eq;
    window.EQ = new eq.EQ();
    window.EQ.dispatch('EQBaseReady');

})();



