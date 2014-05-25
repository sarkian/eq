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
        // TODO: передача префикса через jsdata
        return '/ajax/' + path;
    };

    Ajax.prototype.exec = function(path, data, on_success, on_error) {
        var url = EQ.ajax.url(path);
        $.post(url, data, 'json').done(function(data) {
            console.log(data);
        }).fail(function(data) {
            console.log(data);
        });
    };


    EQ.registerComponent('ajax', Ajax);
    $(function() {
        EQ.ajax.init();
    });

})();

