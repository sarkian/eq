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
    };

    Ajax.prototype.reload = function() {

    };


    EQ.registerComponent('ajax', Ajax);
    $(EQ.ajax.init);

})();


