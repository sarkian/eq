<?php

namespace eq\console;

class Console
{

    public static function printVariants($header, $variants, $pure = false)
    {
        if($pure)
            echo implode(' ', $variants);
        else {
            echo "$header\n";
            foreach($variants as $variant)
                echo "    $variant\n";
        }
    }

}
