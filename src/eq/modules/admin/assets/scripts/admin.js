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

    var loading = null;

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
            delay: 10000,
            animation: 'fade',
            animate_speed: 'fast'
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
                break;
            case 'loadingBegin':
                if(!loading) {
                    o.type = 'info';
                    o.icon = false;
                    o.hide = false;
                    o.buttons.closer = false;
                    loading = new PNotify(opts(o, options));
                }
                else {
                    loading.update({
                        text: message
                    });
                }
                return;
            case 'loadingEnd':
                if(loading) {
                    loading.remove();
                    loading = null;
                }
                return;
            case 'loadingSuccess':
                if(loading) {
                    loading.update({
                        text: message,
                        type: 'success',
                        icon: 'glyphicon glyphicon-ok-sign',
                        hide: true,
                        delay: 1000
                    });
                }
                return;
            case 'loadingError':
                if(loading) {
                    loading.update({
                        text: message,
                        type: 'error',
                        icon: 'glyphicon glyphicon-warning-sign',
                        hide: true,
                        delay: 10000,
                        buttons: {closer: true}
                    });
                }
                return;
        }
        new PNotify(opts(o, options));
    });

});