if(!window.jQuery && window.Zepto) {
    // TODO: $.event.special, $.data
    window.jQuery = window.Zepto;
}

if(window.EQ)
    EQ.wrapJQuery();
