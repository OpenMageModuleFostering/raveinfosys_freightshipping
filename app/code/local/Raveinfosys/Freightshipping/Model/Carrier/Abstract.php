<?php

abstract class Raveinfosys_Freightshipping_Model_Carrier_Abstract extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface
{

    protected $_code = '';
    protected $_result = null;

    /**
     * Fields that should be replaced in debug with '***'
     *
     * @var array
     */
    public $_debugReplacePrivateDataKeys = array();

    /**
     * Collect rates for this shipping method based on information in $request
     *
     * @param Mage_Shipping_Model_Rate_Request $data
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if ($this->getConfigValue('active') && $this->_code != 'bestquote') {
            return false;
        }

        if (!$this->checkAllowedShipCountries($request->getDestCountryId()) || !$this->isPackageWeightAllowed($request->getPackageWeight())) {
            return false;
        }

        $this->_result = $this->getRates($request);

        return $this->getResult();
    }

    public function getResult()
    {
        if ($this->_result) {
            $result = Mage::getModel('shipping/rate_result');
            foreach ($this->_result as $_method) {
                $finalRates = $this->calculateHandlingFee($_method['rates']);
                $method = Mage::getModel('shipping/rate_result_method');
                $method->setCarrier($_method['carrier']);
                $method->setCarrierTitle($_method['carrier']);
                $method->setMethod($_method['code']);
                $method->setMethodTitle($_method['method']);
                $method->setPrice($finalRates);
                $method->setCost($finalRates);
                $result->append($method);
            }
        } elseif ($this->_result === false) {
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle(Mage::getStoreConfig($this->_configPath . '/title'));
            $errorMsg = Mage::getStoreConfig($this->_configPath . '/errormessage');
            $error->setErrorMessage($errorMsg ? $errorMsg : Mage::helper('shipping')->__('This shipping method is currently unavailable. If you would like to ship using this shipping method, please contact us.'));
            return $error;
        } else {
            return false;
        }
        return $result;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        if ($_allowdMethods = $this->getConfigValue('allowed_methods', $this->_code)) {
            $_methods = explode(',', $_allowdMethods);
            foreach ($_methods as $_method) {
                $_methodArray[$_method] = $_method;
            }
            return $_methodArray;
        } else {
            return array($this->_code => $this->getConfigValue('title', $this->_code));
        }
    }

    /**
     * Get Available shipping rates
     *
     * @return array
     */
    public function getRates(Mage_Shipping_Model_Rate_Request $request)
    {
        return $this->_result;
    }

    /**
     * Retrieve information from carrier configuration
     *
     * @return  mixed
     */
    public function getConfigValue($_field = null, $_groupCode = null)
    {
        if (!$_field) {
            return '';
        }
        if ($_groupCode) {
            return Mage::getStoreConfig('freightshipping/' . $_groupCode . '/' . $_field, $this->getStore());
        } else {
            return Mage::getStoreConfig('freightshipping/general/' . $_field, $this->getStore());
        }
    }

    /**
     * Log debug data to file
     *
     * @param mixed $debugData
     */
    public function debugData($debugData)
    {
        if ($this->getConfigValue('debug', $this->_code)) {
            Mage::getModel('core/log_adapter', 'shipping_' . $this->_code . '.log')
                    ->setFilterDataKeys($this->_debugReplacePrivateDataKeys)
                    ->log($debugData);
        }
    }

    public function calculateHandlingFee($_rates, $_code = 'F')
    {
        if ($this->getConfigValue('handlingfee_type', $this->_code) == 'F') {
            $_rates = $_rates + $this->getConfigValue('handlingfee', $this->_code);
        } elseif ($this->getConfigValue('handlingfee_type', $this->_code) == 'P') {
            $_handlingFee = $this->getConfigValue('handlingfee', $this->_code);
            $_rates = $_rates + ($_rates * $_handlingFee / 100);
        }
        return $_rates;
    }

    public function checkAllowedShipCountries($_countryId)
    {
        $speCountriesAllow = $this->getConfigValue('sallowspecific', $this->_code);
        $_speCountriesFlag = false;
        if ($speCountriesAllow && $speCountriesAllow == 1) {
            $availableCountries = array();
            if ($this->getConfigValue('specificcountry', $this->_code)) {
                $availableCountries = explode(',', $this->getConfigValue('specificcountry', $this->_code));
            }
            if ($availableCountries && in_array($_countryId, $availableCountries)) {
                $_speCountriesFlag = true;
            }
        } else {
            if ($_countryId == 'US' || $_countryId == 'CA') {
                $_speCountriesFlag = true;
            }
        }
        return $_speCountriesFlag;
    }

    public function isPackageWeightAllowed($_packageWeight)
    {
        $_minWeight = $this->getConfigValue('min_weight', $this->_code);
        $_maxWeight = $this->getConfigValue('max_weight', $this->_code);
        if ($_minWeight && ($_packageWeight < $_minWeight) || $_maxWeight && ($_packageWeight > $_maxWeight)) {
            return false;
        }
        return true;
    }

}
