<?php
/**
 * Last Change: 2014 Apr 10, 13:37
 */

namespace eq\cgen;

class ViewRenderer
{

    public static function renderFile($__view_file__, $__input_vars_array__ = [])
    {
        ob_start();
        foreach($__input_vars_array__ as $__input_var_name__ => $__input_var_value__) {
            eval("
                $$__input_var_name__ = \$__input_vars_array__[\$__input_var_name__];
            ");
        }
        require $__view_file__;
        $__view_result_string__ = ob_get_contents();
        ob_end_clean();
        return($__view_result_string__);
    }

}
