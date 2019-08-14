<?php

class Raveinfosys_Freightshipping_Model_Source_Destinationtype
{

    public function toOptionArray()
    {
        return array(
            array('value' => 'COM', 'label' => 'Commercial'),
            array('value' => 'RES', 'label' => 'Residential')
        );
    }

}
