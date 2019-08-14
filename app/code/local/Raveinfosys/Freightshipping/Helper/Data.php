<?php

class Raveinfosys_Freightshipping_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Check is module exists and enabled in global config.
     * @param $moduleName
     */
    public function isModuleEnabled($moduleName = null)
    {

        if ($moduleName === null) {
            return false;
        }

        if (!Mage::getConfig()->getNode('modules/' . $moduleName)) {
            return false;
        }

        return true;
    }

    public function getAvailableCarriers()
    {
        $_availableCarriers = array();
        $configFile = Mage::getConfig()->getModuleDir('etc', 'Raveinfosys_Freightshipping') . DS . 'config.xml';
        $string = file_get_contents($configFile);
        $xml = simplexml_load_string($string, 'Varien_Simplexml_Element');
        $_carriers = json_decode(json_encode($xml), true);
        foreach ($_carriers['default']['carriers'] as $key => $carrier) {
            $_availableCarriers[] = $key;
        }
        return $_availableCarriers;
    }

}
