<?php

class Raveinfosys_Freightshipping_Model_Source_Conway_Country
{

    public function toOptionArray()
    {
        $_allowMethods = array(
            array('value' => 'US', 'label' => 'United States'),
            array('value' => 'CN', 'label' => 'Canada'),
        );
        return $_allowMethods;
    }

}
