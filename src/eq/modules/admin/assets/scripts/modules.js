(function() {

    window.ModulePanel = function(mname, el) {
        var self = this;
        this._mname = mname;
        this._el = el;
        this._heading = el.find('.panel-heading');
        this._checkbox = this._heading.find('input[type=checkbox]');
        this._checkbox.change(function() {
            if(self.canToggle())
                EQ.ajax.exec('modules.eq:admin.modules.toggle', {module_name: mname});
        });
    };

    ModulePanel._panels = {};

    ModulePanel.clean = function() {
        ModulePanel._panels = {};
    };

    ModulePanel.register = function(el) {
        el = $(el);
        var mname = el.data('module-name');
        if(!ModulePanel._panels[mname])
            ModulePanel._panels[mname] = new ModulePanel(mname, el);
    };

    ModulePanel.get = function(mname) {
        return ModulePanel._panels[mname];
    };

    ModulePanel.each = function(callback) {
        for(var mname in ModulePanel._panels) {
            if(ModulePanel._panels.hasOwnProperty(mname))
                callback(mname, ModulePanel._panels[mname]);
        }
    };

    ModulePanel.hideSystem = function() {
        ModulePanel.each(function(m, p) {
            if(p.isSystem())
                p.hide();
        });
    };

    ModulePanel.showSystem = function() {
        ModulePanel.each(function(m, p) {
            if(p.isSystem())
                p.show();
        });
    };

    ModulePanel.showNotSystem = function() {
        ModulePanel.each(function(m, p) {
            if(!p.isSystem())
                p.show();
        });
    };

    ModulePanel.showAll = function() {
        ModulePanel.each(function(m, p) {
            p.show();
        });
    };

    ModulePanel.hideAll = function() {
        ModulePanel.each(function(m, p) {
            p.hide();
        });
    };

    ModulePanel.update = function() {
        var b = $('#show-system');
        b.change(function() {
            var val = $(this).is(':checked') ? 1 : 0;
            EQ.udata.set('admin.modules.showSystem', val);
            if(val)
                ModulePanel.showSystem();
            else
                ModulePanel.hideSystem();
        });
        ModulePanel.clean();
        $('.module-panel').each(function(i, el) {
            ModulePanel.register(el);
        });
        $('.module-dependencies a[data-module-name]').click(function() {
            var mname = $(this).data('module-name');
            ModulePanel.get(mname).scrollTo();
            return false;
        });
        if(EQ.udata.get('admin.modules.showSystem', 0) == 1) {
            b.prop('checked', true);
            ModulePanel.showAll();
        }
        else {
            b.prop('checked', false);
            ModulePanel.showNotSystem();
        }
    };

    ModulePanel.prototype = {

        show: function() {
            this._el.show();
        },

        hide: function() {
            this._el.hide();
        },

        setEnabled: function() {
            this._el.removeClass('panel-default panel-danger').addClass('panel-primary');
        },

        setDisabled: function() {
            this._el.removeClass('panel-primary panel-danger').addClass('panel-default');
        },

        setError: function() {
            this._el.removeClass('panel-primary panel-default').addClass('panel-danger');
        },

        canToggle: function() {
            return !this._checkbox.is(':disabled');
        },

        isSystem: function() {
            return this._el.hasClass('system');
        },

        scrollTo: function() {
            var el = this._el;
            var dest = el.position().top - 70;
            $('html,body').animate({
                scrollTop: dest
            }, 300, 'swing', function() {
                if(this !== document.body)
                    return;
                setTimeout(function() {
                    el.removeClass('scrolled-to');
                }, 300);
            });
            el.addClass('scrolled-to');
        }

    };

    EQ.bind('ajax.ready', function() {
        ModulePanel.update();
    });

    $(function() {
        ModulePanel.update();
    });

})();