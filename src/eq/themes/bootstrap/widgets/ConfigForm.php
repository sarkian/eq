<?php

namespace eq\themes\bootstrap\widgets;

class ConfigForm extends \eq\widgets\ConfigForm
{

    use TForm;

    protected function renderFieldset($fieldset)
    {
        return parent::renderFieldset($fieldset)."<br />";
    }

} 