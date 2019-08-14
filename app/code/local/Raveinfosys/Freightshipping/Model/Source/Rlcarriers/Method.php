<?php
class Raveinfosys_Freightshipping_Model_Source_Rlcarriers_Method
{
    public function toOptionArray()
    {   
		$_allowMethods = array(
							array('value'=>'STD', 'label'=>'Standard Service'),							
							array('value'=>'GSDS', 'label'=>'Guaranteed Service'),							
							array('value'=>'GSAM', 'label'=>'Guaranteed AM Service'),
							array('value'=>'GSHW', 'label'=>'Guaranteed HW Service')
						);
        return $_allowMethods;
    }
}
