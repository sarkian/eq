$(function() {

    var stack = {
        addpos2: 0,
        animation: true,
        context: $('body'),
        dir1: "down",
        dir2: "left",
        firstpos1: 65,
        firstpos2: 8,
        nextpos1: 65,
        nextpos2: 8,
        push: "top",
        spacing1: 0,
        spacing2: 0
    };

    function opts(o, uo) {
        return $.extend(true, {
//            type: 'info',
            history: false,
            shadow: false,
            addclass: 'eq-notify',
            stack: stack,
            hide: true,
            closer: false,
            nonblock: false,
            delay: 10000
        }, o, uo);
    }

    EQ.setNotificationHandler(function(message, type, options) {
        options = options || {};
        var o = {
            text: message,
            type: type,
            buttons: {
                closer: true,
                sticker: false
            }
        };
        switch(type) {
            case 'success':
                o.buttons.closer = false;
                o.delay = 1000;
                break;
            case 'info':
            case 'notice':
                o.hide = false;
                break;
            case 'error':
                o.hide = false;
        }
        new PNotify(opts(o, options));
    });

});