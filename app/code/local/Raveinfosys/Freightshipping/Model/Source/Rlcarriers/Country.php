<?php

class Raveinfosys_Freightshipping_Model_Source_Rlcarriers_Country
{

    public function toOptionArray()
    {
        $_allowMethods = array(
            array('value' => 'USA', 'label' => 'United States'),
            array('value' => 'CAN', 'label' => 'Canada'),
        );
        return $_allowMethods;
    }

}
