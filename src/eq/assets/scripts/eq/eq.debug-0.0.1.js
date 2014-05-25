(function() {

    var root = {
        left: null,
        right: null
    };

    function setStyle(el, style) {
        for(var i in style)
            el.style[i] = style[i];
    }

    function el(name, attrs, style) {
        var el = document.createElement(name);
        for(var i in attrs)
            el[i] = attrs[i];
        setStyle(el, style);
        return el;
    }

    function setRoot(side) {
        if(!root[side]) {
            root[side] = el('div', {
                id: 'eq_debug-root-' + side
            }, {});
            document.body.appendChild(root[side]);
        }
    }

    function callStack() {
        var stack = [];
        try {
            non.existent.variable += 0;
        }
        catch(e) {
            if(e.stack)
                stack = e.stack.split('\n');
            if(!/^\s/.test(stack[0]) && /^\s/.test(stack[1]))
                stack.shift();
            stack.shift();
            stack.shift();
            return stack;
        }
    }

    function docHeight() {
        var body = document.body,
            html = document.documentElement;
        return Math.max(body.scrollHeight, body.offsetHeight,
            html.clientHeight, html.scrollHeight, html.offsetHeight);
    }

    function checkHeight(noscroll) {
        for(var i in root) {
            if(!root[i])
                continue;
            var el = root[i];
            if(el.scrollHeight > docHeight()) {
                el.classList.add('eq_debug-root-overflow');
                if(!noscroll)
                    el.scrollTop = el.scrollHeight;
            }
            else
                el.classList.remove('eq_debug-root-overflow');
        }
    }

    function show(type, message, title, cmessage, side) {
        setRoot(side);
        var block = el('div', {
            className: 'eq_debug-message eq_debug-message-' + type,
            onclick: function() {
                if(cmessage)
                    console.log(cmessage);
            },
            oncontextmenu: function() {
                var self = this;
                this.className += ' ' + 'eq_debug-fadeout';
                setTimeout(function() {
                    self.remove();
                    checkHeight(true);
                }, 200);
                return false;
            }
        }, {});
        var title_el = el('span', {
            className: 'eq_debug-message-name'
        }, {});
        var message_el = el('span', {
            className: 'eq_debug-message-text'
        }, {});
        if(title) {
            title_el.textContent = title + ':';
            message_el.textContent = ' ';
        }
        else
            message_el.textContent = '';
        if (type === 'warning' && /^(TODO|FIXME): /.test(message.trim())) {
            message_el.innerHTML = message.trim().replace(/^(TODO|FIXME): /,
                '\n<b class="eq_debug-message-todo">$1: </b>');
        }
        else
            message_el.textContent += message;
        block.appendChild(title_el);
        block.appendChild(message_el);
        root[side].appendChild(block);
        checkHeight(false);
    }


    var Debug = function() {
        
    };

    Debug.prototype.err = function(message, title, cmessage, side) {
        title = title || 'Error';
        cmessage = cmessage || callStack().join('\n');
        side = side === 'left' || side === 'right' ? side : 'left';
        show('error', message, title, cmessage, side);
    };

    Debug.prototype.warn = function(message, title, cmessage, side) {
        title = title || 'Warning';
        cmessage = cmessage || callStack().join('\n');
        side = side === 'left' || side === 'right' ? side : 'left';
        show('warning', message, title, cmessage, side);
    };

    Debug.prototype.info = function(message, title, cmessage, side) {
        title = title || 'Info';
        cmessage = cmessage || callStack().join('\n');
        side = side === 'left' || side === 'right' ? side : 'left';
        show('info', message, title, cmessage, side);
    };


    EQ.registerComponent('dbg', Debug);

    EQ.bind('error', function(e) {
        console.log(callStack().join('\n'));
        show('error', e.message, e.name, e.stack, 'left');
    });

    EQ.dispatchOn('DOMContentLoaded', 'EQDbgReady');


    window.addEventListener('resize', checkHeight);
    
})();
