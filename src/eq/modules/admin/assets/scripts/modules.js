$(function() {

    window.ModulePanel = function(el) {
        var self = this;
        this._el = el;
        this._heading = el.find('.panel-heading');
        this._checkbox = this._heading.find('input[type=checkbox]');
        this._checkbox.change(function() {
            if(!self.canDisable())
                return;
            if($(this).is(':checked'))
                self.setEnabled();
            else
                self.setDisabled();
        });
    };

    ModulePanel._panels = {};

    ModulePanel.register = function(el) {
        el = $(el);
        var mname = el.data('module-name');
        if(!ModulePanel._panels[mname])
            ModulePanel._panels[mname] = new ModulePanel(el);
    };

    ModulePanel.get = function(mname) {
        return ModulePanel._panels[mname];
    };

    ModulePanel.each = function(callback) {
        for(var mname in ModulePanel._panels)
            callback(mname, ModulePanel._panels[mname]);
    };

    ModulePanel.hideSystem = function() {
        ModulePanel.each(function(m, p) {
            if(!p.canDisable())
                p.hide();
        });
    };

    ModulePanel.showSystem = function() {
        ModulePanel.each(function(m, p) {
            if(!p.canDisable())
                p.show();
        });
    };

    ModulePanel.showNotSystem = function() {
        ModulePanel.each(function(m, p) {
            if(p.canDisable())
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

        canDisable: function() {
            return !this._checkbox.is(':disabled');
        },

        scrollTo: function() {

        }

    };


    $('.module-panel').each(function(i, el) {
        ModulePanel.register(el);
    });

    if(EQ.udata.get('admin.modules.showSystem', 0) == 1)
        ModulePanel.showAll();
    else
        ModulePanel.showNotSystem();

});