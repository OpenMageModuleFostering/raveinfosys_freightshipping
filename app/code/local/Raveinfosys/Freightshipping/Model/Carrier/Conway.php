<?php

class Raveinfosys_Freightshipping_Model_Carrier_Conway extends Raveinfosys_Freightshipping_Model_Carrier_Abstract
{
    /* Shipping Carrier Code */

    public $_code = 'conway';

    /* Con-way test user credentials */
    protected $_username = 'demo';
    protected $_password = 'demo';

    /* Con-way customer number & Ship code, to get customer specific discount */
    protected $_custNmbr = null;
    protected $_shipCode = null;

    /* Con-way request quote URL */
    protected $_requestUrl = 'https://www.con-way.com/XMLj/X-Rate';

    public function isTrackingAvailable()
    {
        return true;
    }

    public function getTrackingInfo($tracking)
    {
        $track = Mage::getModel('shipping/tracking_result_status');
        $track->setUrl('http://www.con-way.com/webapp/manifestrpts_p_app/Tracking/Tracking.jsp')
                ->setTracking($tracking)
                ->setCarrierTitle($this->getConfigValue('title', $this->_code));
        return $track;
    }

    public function setConwayData()
    {
        $this->_username = Mage::helper('core')->decrypt($this->getConfigValue('userid', $this->_code));
        $this->_password = Mage::helper('core')->decrypt($this->getConfigValue('password', $this->_code));
        $this->_custNmbr = $this->getConfigValue('customerno', $this->_code);
        $this->_shipCode = $this->getConfigValue('shipcode', $this->_code);
    }

    public function getRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigValue('active', $this->_code)) {
            return;
        }

        /*         * ****Set defualt module config data****** */
        if (!($_originZip = $this->getConfigValue('origin'))) {
            $_originZip = Mage::getStoreConfig('shipping/origin/postcode');
        }
        $_defaultShipClass = $this->getConfigValue('shipclass');

        $_debugData = array();
        $this->setConwayData();
        $accArray = array();
        $xmlRequest = "<RateRequest>" .
                "<OriginZip country=\"us\">" . $_originZip . "</OriginZip>" .
                "<DestinationZip country=\"us\">" . $request->getDestPostcode() . "</DestinationZip>";

        if ($this->_custNmbr) {
            $xmlRequest .= "<CustNmbr shipcode=\"" . $this->_shipCode . "\">" . $this->_custNmbr . "</CustNmbr>";
        }

        /* $xmlRequest .=	"<ChargeCode>P</ChargeCode>
          <EffectiveDate>$_effectedDate</EffectiveDate>"; */

        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $_item) {

                if ($_item->getProduct()->isVirtual() || $_item->getParentItem()) {
                    continue;
                }

                if ($_item->getHasChildren()) {
                    foreach ($_item->getChildren() as $child) {
                        if ($child->getProduct()->isVirtual()) {
                            continue;
                        }
                        $_product = Mage::getModel('catalog/product')->load($child->getProductId());
                        $_productWeight = ceil($_product->getWeight() * $_item->getQty());
                        $_productFreightClass = $_product->getFreightClass() ? $_product->getFreightClass() : $_defaultShipClass;
                        $xmlRequest .= "<Item>" .
                                "<CmdtyClass>" . $_productFreightClass . "</CmdtyClass>" .
                                "<Weight unit='lbs'>" . $_productWeight . "</Weight>" .
                                "</Item>";
                    }
                } else {
                    $_product = Mage::getModel('catalog/product')->load($_item->getProductId());
                    $_productWeight = ceil($_item->getProduct()->getWeight() * $_item->getQty());
                    $_productFreightClass = $_product->getFreightClass() ? $_product->getFreightClass() : $_defaultShipClass;
                    $xmlRequest .= "<Item>" .
                            "<CmdtyClass>" . $_productFreightClass . "</CmdtyClass>" .
                            "<Weight unit='lbs'>" . $_productWeight . "</Weight>" .
                            "</Item>";
                }
            }
        }
        $accArray = $this->getAccessorialServices();

        if (count($accArray) > 0) {
            foreach ($accArray as $acc) {
                $xmlRequest .= "<Accessorial>$acc</Accessorial>";
            }
        }
        $_debugData['request'] = $xmlRequest;
        $xmlRequest .= "</RateRequest>";

        try {
            $xmlRequest = urlencode($xmlRequest);
            $urlConn = curl_init($this->_requestUrl);
            curl_setopt($urlConn, CURLOPT_POST, 1);
            curl_setopt($urlConn, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($urlConn, CURLOPT_HTTPHEADER, array("Content-type: application/x-www-form-urlencoded"));
            curl_setopt($urlConn, CURLOPT_USERPWD, $this->_username . ":" . $this->_password);
            curl_setopt($urlConn, CURLOPT_POSTFIELDS, "RateRequest=$xmlRequest");
            ob_start();
            curl_exec($urlConn);
            $xmlResponse = simplexml_load_string(ob_get_contents());
            ob_end_clean();
            curl_close($urlConn);
        } catch (Exception $e) {
            return false;
        }
        $_debugData['result'] = $xmlResponse;
        if ($xmlResponse->xpath('//' . 'Error')) {
            $this->debugData($_debugData);
            return false;
        }
        $_results = $this->parseXmlResponse($xmlResponse);
        $_debugData['result'] = $_results;
        $this->debugData($_debugData);
        return $_results;
    }

    protected function parseXmlResponse($xmlResponse)
    {
        $_rates = array();
        $myElements = array('NetCharge');
		//print_r($myElements);exit;
        for ($i = 0; $i < count($xmlResponse->xpath('//' . $myElements[0])); $i++) {
            foreach ($myElements as $myEl) {
                $myVals = $xmlResponse->xpath('//' . $myEl);
                $response[$myEl] = $myVals[$i];
            }
        }
        $_rates[] = array('rates' => (float) preg_replace('/[^0-9.]*/', '', $response['NetCharge']), 'carrier' => $this->_code, 'method' => 'Conway', 'code' => $this->_code);
        return $_rates;
    }

    protected function getAccessorialServices()
    {
        $accArray = array();
        if ($this->getConfigValue('dest_type') == 'RES') {
            $accArray[] = 'RSD';
        }
        if ($this->getConfigValue('inside_delivery')) {
            $accArray[] = 'DID';
        }
        if ($this->getConfigValue('liftgate')) {
            $accArray[] = 'DLG';
        }
        if ($this->getConfigValue('dest_notify')) {
            $accArray[] = 'DNC';
        }
        return $accArray;
    }

}

?>