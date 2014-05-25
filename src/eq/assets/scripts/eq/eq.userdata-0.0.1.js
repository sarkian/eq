(function() {

    var Userdata = function() {
        return this;
    };

    Userdata.prototype.get = function(name, _default) {
        var value = $.cookie('eq.udata.' + name);
        return value === undefined ? _default : value;
    };

    Userdata.prototype.set = function(name, value) {
        $.cookie('eq.udata.' + name, value, { expires: 365 * 29, path: '/' });
        return value;
    };

    Userdata.prototype.unset = function(name) {
        return $.removeCookie('eq.udata.' + name, { path: '/' });
    };

    EQ.registerComponent('udata', Userdata);

    EQ.dispatchOn('DOMContentLoaded', 'EQUserdataReady');

})();