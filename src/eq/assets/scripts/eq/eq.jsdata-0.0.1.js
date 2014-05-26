(function() {

    var data = {};

    var Data = function(_data) {
        data = _data;
        return this;
    };

    Data.prototype.get = function(key, _default) {
        if(typeof key !== 'string')
            return data;
        var val = data;
        var keys = key.split('.').filter(function(n) { return n.length; });
        if(!keys)
            throw new eq.Exception('Invalid key: ' + key);
        for(var i in keys) {
            if(!keys.hasOwnProperty(i))
                continue;
            var k = keys[i];
            if(val && val[k])
                val = val[k];
            else
                return _default;
        }
        return val;
    };

    Data.prototype.set = function(key, value) {
        if(typeof key !== 'string')
            throw new eq.Exception('Invalid key: ' + key);
        var val = data;
        var keys = key.split('.').filter(function(n) { return n.length; });
        if(!keys)
            throw new eq.Exception('Invalid key: ' + key);
        var lastkey = keys.pop();
        for(var i in keys) {
            if(!keys.hasOwnProperty(i))
                continue;
            var k = keys[i];
            if(typeof val[k] !== 'object')
                val[k] = {};
            val = val[k];
        }
        val[lastkey] = value;
    };


    window.eq.Jsdata = Data;

//    EQ.registerComponent('data', new Data());

//    EQ.registerComponent('_data', Data);
//    EQ.registerComponent('data', function() {
//        return function(key, _default) {
//            return EQ._data.get(key, _default);
//        };
//    });

//    EQ.dispatchOn('DOMContentLoaded', 'EQDataReady');

})();


