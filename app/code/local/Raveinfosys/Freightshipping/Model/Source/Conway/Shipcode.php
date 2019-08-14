<?php

class Raveinfosys_Freightshipping_Model_Source_Conway_Shipcode
{

    public function toOptionArray()
    {
        $_allowMethods = array(
            array('value' => 'S', 'label' => 'S'),
            array('value' => 'C', 'label' => 'C'),
            array('value' => '3', 'label' => '3'),
        );
        return $_allowMethods;
    }

}
