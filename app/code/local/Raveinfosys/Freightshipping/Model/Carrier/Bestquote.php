<?php
class Raveinfosys_Freightshipping_Model_Carrier_Bestquote extends Raveinfosys_Freightshipping_Model_Carrier_Abstract
{
	public $_code = 'bestquote';

	public function getRates(Mage_Shipping_Model_Rate_Request $request)
    {
		if(!$this->getConfigValue('active')){
			return;
		}
		$_carriers = $this->getAvailableCarriers();		
		$_cheapestRate = 0;
		foreach($_carriers as $_carrier){			
			$model = Mage::getModel($_carrier);
			$_rates = $model->getRates($request);
			if($_rates){
				foreach($_rates as $_rate){
					$_rate['rates'] = $this->calculateHandlingFee($_rate['rates'],$_rate['carrier']);
					if($_cheapestRate == 0 || $_cheapestRate[0]['rates'] > $_rate['rates'] ){
						$_cheapestRate = array($_rate);
					}
				}
			}
		}		
		return $_cheapestRate;
	}
	
	public function calculateHandlingFee($_rates,$_code)
	{	
		if($this->getConfigValue('handlingfee_type', $_code) == 'F') {
			$_rates = $_rates + $this->getConfigValue('handlingfee', $_code);
		} elseif($this->getConfigValue('handlingfee_type', $_code) == 'P') {
			$_handlingFee =  $this->getConfigValue('handlingfee', $_code);
			$_rates = $_rates + ($_rates*$_handlingFee/100);
		}		
		return $_rates;
	}
	
	protected function getAvailableCarriers()
	{
		$_availableCarriers = array();
		$configFile = Mage::getConfig()->getModuleDir('etc', 'Raveinfosys_Freightshipping').DS.'config.xml';
		$string = file_get_contents($configFile);
		$xml = simplexml_load_string($string, 'Varien_Simplexml_Element');
		$_carriers = json_decode(json_encode($xml), true);		
		foreach($_carriers['default']['carriers'] as $key => $carrier){
			if($key != $this->_code){
				$_availableCarriers[$key] = $carrier['model'];
			}
		}		
		return $_availableCarriers;
	}
}



?>