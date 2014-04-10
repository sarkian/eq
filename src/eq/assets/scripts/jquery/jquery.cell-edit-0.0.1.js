(function($) {
    $.fn.setCursorPosition = function(pos) {
        if($(this).get(0).setSelectionRange)
            $(this).get(0).setSelectionRange(pos, pos);
        else if($(this).get(0).createTextRange) {
            var range = $(this).get(0).createTextRange();
            range.collapse(true);
            range.moveEnd('character', pos);
            range.moveStart('character', pos);
            range.select();
        }
    };
})(jQuery);

(function($) {

    jQuery.fn.cellEdit = function(options) {

        options = $.extend({
            initW: 250,
            initH: 50,
            speed: 'fast',
            addclass: 'cell-edit',
            css: {},
            onOpen: function(text, elem) {},
            onClose: function(text, elem) {},
            onTextChange: function(text, elem, oldtext) {},
            textFilter: function(text) { return text; },
            useDataAttr: null,
            disabled: false
        }, options);

        var self = this;
        var current;
        var area;
        var textBefore, textAfter;

        function __close() {
            $('html').unbind('click', __clickClose);
            $('html').unbind('keydown', __kbdClose);
            textAfter = options.textFilter(area.val());
            options.onClose(textAfter, current);
            if(textBefore !== textAfter) {
                options.onTextChange(textAfter, current, textBefore);
                $(current).text(textAfter);
            }
            area.animate({
                width: 0,
                height: 0
            }, options.speed, function() {
                area.remove();
                self.bind('click', __open);
            });
        }

        function __clickClose(event) {
            if(event.target === current || event.target === area[0])
                return true;
            __close();
        }

        function __kbdClose(event) {
            if(event.keyCode === 27 || (event.keyCode === 13 && event.ctrlKey))
                __close();
        }

        function __open() {
            current = this;
            self.unbind('click', __open);
            $('html').bind('click', __clickClose);
            $('html').bind('keydown', __kbdClose);
            textBefore = options.useDataAttr ? $(this).data(options.useDataAttr) : $(this).text();
            options.onOpen(textBefore, current);
            // var pos = $(this).position();
            var pos = $(this).offset();
            area = $('<textarea>', {class: options.addclass});
            area.css({
                position: 'absolute',
                left: pos.left + 'px',
                top: pos.top + 'px',
                'z-index': '9000',
                display: 'inline-block',
                width: 0,
                height: 0
            });
            area.css(options.css);
            area.val(textBefore);
            if(options.disabled)
                area.attr('disabled', 'disabled');
            $('body').append(area);
            area.animate({
                width: options.initW,
                height: options.initH
            }, options.speed, function() {
                area.focus();
                area.setCursorPosition(textBefore.length);
            });
        };

        $(this).bind('click', __open);
        
    };

    
})(jQuery);
