<?php
class Raveinfosys_Freightshipping_Block_Onepage_Shipping_Method_Available 
	extends Mage_Checkout_Block_Onepage_Shipping_Method_Available
{
	public function getCarrierName($carrierCode)
    {		
        $_helper = Mage::helper('freightshipping');
		$_carriers = $_helper->getAvailableCarriers();
		if(in_array($carrierCode, $_carriers) && $name = Mage::getStoreConfig('freightshipping/'.$carrierCode.'/title')){
			return $name;
		}
        if ($name = Mage::getStoreConfig('carriers/'.$carrierCode.'/title')) {			
            return $name;
        }
        return $carrierCode;
    }
	
}