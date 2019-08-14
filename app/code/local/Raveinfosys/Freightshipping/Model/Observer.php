<?php
class Raveinfosys_Freightshipping_Model_Observer
{
	public function saveShippingMethodBefore($evt)
	{
        $quote = $evt->getQuote();

		if($method = $quote->getShippingAddress()->getShippingMethod()) {

			$array = explode('_',$method);

			if($_shippingDescription = Mage::getStoreConfig('freightshipping/'.$array[0].'/title')) {

				$_description = $quote->getShippingAddress()->getShippingDescription();

				$quote->getShippingAddress()->setShippingDescription(str_replace($array[0], $_shippingDescription, $_description));
			}
		}
	}
	
	public function updateShippingDescription($evt){
		$order = $evt->getOrder();
		$_order = Mage::getModel('sales/order')->load($order->getId());
		if($_order->getData('shipping_method')) {
			$array = explode('_',$_order->getData('shipping_method'));
			if($_shippingDescription = Mage::getStoreConfig('freightshipping/'.$array[0].'/title')){
				$_description = str_replace($array[0], $_shippingDescription, $_order->getData('shipping_description'));
				$_order->setData('shipping_description', $_description);
				$order->setData('shipping_description', $_description);
				$_order->save()->unsetData();
			}
		}
	}
}