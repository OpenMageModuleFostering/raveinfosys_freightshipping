<?php
class Raveinfosys_Freightshipping_Model_Carrier_Rlcarriers extends Raveinfosys_Freightshipping_Model_Carrier_Abstract
{
	/* Shipping Carrier Code */
	protected $_code = 'rlcarriers';

	/* API Key to get request quote access*/
	protected $_apiKey = null;

	/* Request quote API URL*/
	protected $_apiUrl = 'http://api.rlcarriers.com/1.0.1/RateQuoteService.asmx?WSDL';

	public function isTrackingAvailable()
	{
		return true;
	}

	public function getTrackingInfo($tracking)
	{
		$track = Mage::getModel('shipping/tracking_result_status');
		$track->setUrl('http://www2.rlcarriers.com/freight/shipping/shipment-tracing')
			->setTracking($tracking)
			->setCarrierTitle($this->getStoreConfig('title', $this->_code));
		return $track;
	}    

	public function getRates(Mage_Shipping_Model_Rate_Request $request)
    {		
		if(!$this->getConfigValue('active', $this->_code)){
			return;
		}
		try {
		/******Set defualt module config data*******/
		if(!($_originZip = $this->getConfigValue('origin'))){			
			$_originZip = Mage::getStoreConfig('shipping/origin/postcode');
		}		
		$_defaultShipClass = $this->getConfigValue('shipclass');		
		$this->_apiKey	= Mage::helper('core')->decrypt($this->getConfigValue('apikey', $this->_code));
		if(!$this->_apiKey){
			return false;
		}
		$_rlcRequest["APIKey"] = $this->_apiKey;		
		$_rlcRequest["QuoteType"] = "Domestic";
		$_rlcRequest["CODAmount"] = "0";
		$_rlcRequest["Origin"] = array(
									"City"				=>	"",
									"StateOrProvince"	=>	"",
									"ZipOrPostalCode"	=>	$_originZip,
									"CountryCode"		=>	$this->getCountryIso3Code($request->getCountryId())
									);
		$_rlcRequest["Destination"] = array(
										"City"				=>	"",
										"StateOrProvince"	=>	"",
										"ZipOrPostalCode"	=>	trim($request->getDestPostcode()),
										"CountryCode"		=>	$this->getCountryIso3Code($request->getDestCountryId()),
							);
		$_rlcRequest["DeclaredValue"] = 0;

		$_rlcRequest["OverDimensionPcs"] = 0;

		$items = array();

		$_debugData = array();

		$cn = 0;
		foreach ($request->getAllItems() as $_item) {

			if ($_item->getProduct()->isVirtual() || $_item->getParentItem()) {
                    continue;
			}

			if($_item->getHasChildren()){					
				foreach ($_item->getChildren() as $child){
					if($child->getProduct()->isVirtual()){
							continue;
					}
					$_product = Mage::getModel('catalog/product')->load($child->getProductId());
					$items[$cn]['Class'] = (float)($_product->getFreightClass() ? $_product->getFreightClass() : $_defaultShipClass);
					$items[$cn]['Weight'] = (float)ceil($_product->getWeight()*$_item->getQty());
					$items[$cn]['Height'] = (float)$_product->getFreightWidth();
					$items[$cn]['Width'] = (float)$_product->getFreightHeight();
					$items[$cn]['Length'] = (float)$_product->getFreightLength();
					$cn++;
				}
			} else {
				$_product = Mage::getModel('catalog/product')->load($_item->getProduct()->getId());
				$items[$cn]['Class'] = (float)($_product->getFreightClass() ? $_product->getFreightClass() : $_defaultShipClass);
				$items[$cn]['Weight'] = (float)ceil($_product->getWeight()*$_item->getQty());
				$items[$cn]['Height'] = (float)$_product->getFreightWidth();
				$items[$cn]['Width'] = (float)$_product->getFreightHeight();
				$items[$cn]['Length'] = (float)$_product->getFreightLength();
				$cn++;
			}
		}	

		$_rlcRequest["Accessorials"] = $this->getAccessorialServices();	
		
		$_rlcRequest["Items"] = $items;
		
		$_debugData['request'] = $_rlcRequest;		
		
		try	{			
			$client = new SoapClient($this->_apiUrl);
			$response = $client->GetRateQuote(array("APIKey"=> $this->_apiKey, "request" => $_rlcRequest) );
			unset($client);			
			if(!$response->GetRateQuoteResult->WasSuccess){
				$_debugData['Error'] = (array)$response;
				$this->debugData($_debugData);
				return false;
			}
			$_results = $this->parseXmlResponse($response);
		
		} catch(SoapFault $fault) {
			$_debugData['Exception'] = $fault;
			$this->debugData($_debugData);
			return false;
		}		

		$_debugData['result'] = $_results;
		
		$this->debugData($_debugData);
		
		return $_results;
		} catch(Exception $e) {
			Mage::log($e->getMessage());
			return false;
		}
    }

	public function parseXmlResponse($response){
		$_rates = array();
		$_result = $response->GetRateQuoteResult->Result;
		$_rlcRates = $_result->ServiceLevels->ServiceLevel;
		$_allowdMethods = $this->getConfigValue('allowed_methods', $this->_code);
		if(is_array($_rlcRates)){
			$_methods = explode(',', $_allowdMethods);
			foreach($_rlcRates as $_rlcRate){
				if(!in_array($_rlcRate->Code, $_methods)){
					continue;
				}
				$_rate = preg_replace('/[^0-9.]* /', '', str_replace('$','',$_rlcRate->NetCharge));
				$_rates[] = array('rates' => $_rate, 'method' => $_rlcRate->Title, 'code' => $_rlcRate->Code, 'carrier' => $this->_code);
				if($this->getConfigValue('active')){
					break;
				}
			}
		} else {
			$_rate = preg_replace('/[^0-9.]*/', '', str_replace('$','',$_result->ServiceLevels->ServiceLevel->NetCharge));
			$_rates[] = array('rates' => $_rate, 'carrier' => $this->_code, 'method' => $_result->ServiceLevels->ServiceLevel->Title, 'code' => $_result->ServiceLevels->ServiceLevel->Code);
		}
		return $_rates;
	}

	protected function getAccessorialServices(){
		$accArray = array();		
		if($this->getConfigValue('dest_type') == 'RES'){
			$accArray[] = 'ResidentialDelivery';
		}
		if($this->getConfigValue('inside_delivery')){
			$accArray[] = 'InsideDelivery';
		}
		if($this->getConfigValue('liftgate')){
			$accArray[] = 'DestinationLiftgate';
		}
		if($this->getConfigValue('dest_notify')){
			$accArray[] = 'DeliveryNotification';
		}
		return $accArray;
	}

	protected function getCountryIso3Code($_countryId){
		$_coutryCollection = Mage::getModel('directory/country');
		return $_coutryCollection->loadByCode($_countryId)->getIso3Code();
	}	
}