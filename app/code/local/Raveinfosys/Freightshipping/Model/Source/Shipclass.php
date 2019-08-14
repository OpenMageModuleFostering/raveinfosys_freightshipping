<?php
/*  */

class Raveinfosys_Freightshipping_Model_Source_Shipclass
{
    public function toOptionArray()
    {
		$_classes = array('', 50, 55, 60, 65, 70, 85, 100, 110, 125, 150, 175, 200, 250, 300, 400, 500, 775, 925);		
		
		$_shipclassArray = array();
		
		foreach($_classes as $_class){
			
			$_shipclassArray[] = array(
									'value'=>$_class,
									'label'=>$_class
								);
		}		
        
		return $_shipclassArray;
    }
}
