(function() {

    var Data = function() {
        return this;
    };

    var data = {};

    Data.prototype.get = function(key, _default) {
        if(!key)
            return data;
        var val = data;
        var keys = key.split('.').filter(function(n) { return n.length; });
        if(!keys)
            throw new eq.Exception('Invalid key: ' + key);
        for(var i in keys) {
            var k = keys[i];
            if(val && val[k])
                val = val[k];
            else
                return _default;
        }
        return val;
    };

    Data.prototype.set = function(key, value) {
        // TODO implement (а надо ли?)
    };


    EQ.registerComponent('_data', Data);
    EQ.registerComponent('data', function() {
        return function(key, _default) {
            return EQ._data.get(key, _default);
        };
    });

    EQ.dispatchOn('DOMContentLoaded', 'EQDataReady');

})();


